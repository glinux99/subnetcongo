<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
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

// État des filtres
const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? null);
const level = ref(props.filters?.level ?? null);
const priceRange = ref(props.filters?.price_range ?? null);
const sort = ref(props.filters?.sort ?? 'popular');

// Placeholder par défaut si l'image échoue
const PLACEHOLDER_IMAGE = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600';

const stats = computed(() => ({
    total: props.courses?.total ?? 0,
    free: (props.courses?.data ?? []).filter(c => !c.price || c.price == 0).length,
    paid: (props.courses?.data ?? []).filter(c => c.price > 0).length,
    avg_rating: 4.8,
}));

// Options de filtrage
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
    { label: 'Plus populaires', value: 'popular' },
    { label: 'Nouveautés', value: 'newest' },
    { label: 'Prix croissant', value: 'price_low' },
];

const apply = () => {
    router.get(route('marketplace.index'), {
        search: search.value || undefined,
        category: category.value || undefined,
        level: level.value || undefined,
        price_range: priceRange.value || undefined,
        sort: sort.value,
    }, { preserveState: true, preserveScroll: true });
};

// Gestionnaires d'actions
const addToCart = (course) => router.post(route('marketplace.cart.add', course.id), {}, { preserveScroll: true });
const toggleWishlist = (course) => router.post(route('marketplace.wishlist.toggle', course.id), {}, { preserveScroll: true });

// Gestionnaire d'affichage d'image
const getImageUrl = (path) => {
    if (!path) return PLACEHOLDER_IMAGE;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

// Fonction pour gérer l'erreur de chargement d'image
const handleImageError = (event) => {
    event.target.src = PLACEHOLDER_IMAGE;
};

const formatPrice = (price) => {
    if (!price || price == 0) return 'Gratuit';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);
};
</script>

<template>
    <AppLayout>
        <Head title="Marketplace | SubnetCongo Academy" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans pb-20">

            <!-- HEADER ULTIMATE V30 (Premium Dark Mesh) -->
            <div class="bg-[#0F172A] pt-12 pb-24 px-6 lg:px-12 relative overflow-hidden">
                <div class="absolute top-[-10%] right-[-10%] w-[600px] h-[600px] bg-violet-600/20 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-indigo-600/10 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                    <div class="text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3 mb-4">
                            <span class="px-3 py-1 bg-violet-500/10 border border-violet-500/20 rounded-full text-[10px] font-black text-violet-400 uppercase tracking-widest">Digital Learning Hub</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic uppercase leading-none">
                            Catalogue <span class="text-violet-500">Marketplace</span>
                        </h1>
                        <p class="text-slate-400 mt-4 max-w-lg text-sm font-medium leading-relaxed">
                            Apprenez des meilleurs experts tech en RDC. Des formations certifiantes pour propulser votre carrière.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link :href="route('marketplace.cart')" class="group relative px-6 py-3 bg-white/5 hover:bg-white/10 backdrop-blur-xl border border-white/10 rounded-2xl flex items-center gap-3 transition-all">
                            <i class="pi pi-shopping-cart text-white text-lg group-hover:scale-110 transition-transform"></i>
                            <div class="text-left">
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Panier</p>
                                <p class="text-white font-bold text-xs">{{ props.cartCount || 0 }} article(s)</p>
                            </div>
                            <span v-if="props.cartCount" class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-[#0F172A]">{{ props.cartCount }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Global Search Pro -->
                <div class="max-w-3xl mx-auto mt-10 relative z-10">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText v-model="search" @keyup.enter="apply" placeholder="Qu'allez-vous maîtriser aujourd'hui ?"
                            class="w-full !bg-white/5 !backdrop-blur-md !border-white/10 !rounded-1xl !py-5 !pl-14 !text-white placeholder:!text-slate-500 focus:!bg-white/10 focus:!border-violet-500/50 transition-all" />
                    </IconField>
                </div>
            </div>

            <!-- CONTENT SECTION -->
            <div class="max-w-11xl mx-auto px-6 -mt-12 relative z-20">
                <div class="bg-white rounded-[1rem] shadow-2xl border border-slate-200/50 p-8 lg:p-10">

                    <!-- KPI STRIP (Premium Cards) -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                        <div v-for="stat in [
                            { label: 'Cours dispos', val: stats.total, icon: 'shopping-bag', color: 'bg-violet-50 text-violet-600' },
                            { label: 'Gratuits', val: stats.free, icon: 'gift', color: 'bg-emerald-50 text-emerald-600' },
                            { label: 'Certifiants', val: stats.paid, icon: 'star', color: 'bg-amber-50 text-amber-600' },
                            { label: 'Rating Global', val: stats.avg_rating, icon: 'verified', color: 'bg-indigo-50 text-indigo-600', suffix: '/5' }
                        ]" :key="stat.label" class="p-6 rounded-1xl border border-slate-100 bg-white hover:shadow-lg transition-all group">
                            <div :class="stat.color" class="w-10 h-10 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i :class="`pi pi-${stat.icon}`"></i>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ stat.label }}</p>
                            <h3 class="text-2xl font-black text-slate-800">{{ stat.val }}<span v-if="stat.suffix" class="text-sm opacity-50">{{ stat.suffix }}</span></h3>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-10">
                        <!-- Sidebar Filters -->
                        <aside class="lg:w-72 space-y-8">
                            <div class="sticky top-10">
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-800 flex items-center gap-2 mb-6">
                                    <span class="w-2 h-2 bg-violet-600 rounded-full"></span> Filtres Avancés
                                </h3>

                                <div class="space-y-6">
                                    <div v-for="f in [{l:'Catégorie', m:category, o:props.categories, p:'Toutes'}, {l:'Niveau', m:level, o:levelOptions}, {l:'Prix', m:priceRange, o:priceOptions}]" :key="f.l">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">{{ f.l }}</label>
                                        <Select v-model="f.m" :options="f.o" :optionLabel="f.l === 'Catégorie' ? 'name' : 'label'" :optionValue="f.l === 'Catégorie' ? 'id' : 'value'" :placeholder="f.p" class="w-full !rounded-xl !bg-slate-50 border-none" />
                                    </div>

                                    <Button label="Filtrer les résultats" icon="pi pi-sliders-h" @click="apply" class="!bg-[#0F172A] hover:!bg-black !border-none !rounded-xl !w-full !py-4 !text-xs !font-black !uppercase !tracking-widest transition-all active:scale-95 shadow-xl" />
                                </div>
                            </div>
                        </aside>

                        <!-- Course Grid -->
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-8 border-b border-slate-100 pb-6">
                                <p class="text-sm font-medium text-slate-500">
                                    Affichage de <span class="text-slate-900 font-bold">{{ props.courses?.data?.length || 0 }}</span> sur <span class="text-slate-900 font-bold">{{ props.courses?.total || 0 }}</span> cours
                                </p>
                                <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" @change="apply" class="!w-48 !bg-transparent border-none !text-xs !font-bold" />
                            </div>

                            <!-- Empty State -->
                            <div v-if="!props.courses?.data?.length" class="py-20 text-center bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-200">
                                <i class="pi pi-search text-5xl text-slate-300 mb-6 block"></i>
                                <h4 class="text-xl font-black text-slate-800">Aucun résultat trouvé</h4>
                                <p class="text-slate-500 text-sm mt-2">Essayez d'ajuster vos filtres de recherche.</p>
                            </div>

                            <!-- Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                                <div v-for="course in props.courses.data" :key="course.id" class="group bg-white rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:border-violet-200 transition-all duration-500 overflow-hidden">
                                    <!-- Image avec gestion d'erreur -->
                                    <div class="relative h-48 overflow-hidden bg-slate-100">
                                        <Link :href="route('marketplace.show', course.id)">
                                            <img
                                                :src="getImageUrl(course.thumbnail_path || course.image)"
                                                @error="handleImageError"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                                alt="Course Thumbnail"
                                            />
                                        </Link>
                                        <div class="absolute top-4 right-4">
                                            <button @click="toggleWishlist(course)" class="w-10 h-10 bg-white/90 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg hover:bg-white transition-colors">
                                                <i :class="props.wishlistIds?.includes(course.id) ? 'pi pi-heart-fill text-rose-500' : 'pi pi-heart text-slate-400'" class="text-sm"></i>
                                            </button>
                                        </div>
                                        <div class="absolute bottom-4 left-4 flex gap-2">
                                            <Tag v-if="props.enrolledIds?.includes(course.id)" value="Déjà inscrit" severity="success" class="!rounded-lg !text-[8px] !font-black !px-2 !uppercase" />
                                            <Tag v-else :value="course.level || 'Tous niveaux'" class="!bg-slate-900/80 !text-white !rounded-lg !text-[8px] !font-black !px-2 !uppercase" />
                                        </div>
                                    </div>

                                    <div class="p-6 space-y-4">
                                        <div class="space-y-1">
                                            <p class="text-[10px] font-black text-violet-600 uppercase tracking-widest">{{ course.category?.name || 'Technologie' }}</p>
                                            <Link :href="route('marketplace.show', course.id)">
                                                <h3 class="text-sm font-black text-slate-800 leading-tight line-clamp-2 hover:text-violet-600 transition-colors tracking-tight">
                                                    {{ course.title }}
                                                </h3>
                                            </Link>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="w-6 h-6 rounded-full bg-slate-200 overflow-hidden border border-slate-100">
                                                <img :src="course.instructor?.avatar || 'https://ui-avatars.com/api/?name=' + course.instructor?.name" @error="handleImageError" class="w-full h-full object-cover" />
                                            </div>
                                            <span class="text-xs font-bold text-slate-500 italic">{{ course.instructor?.name }}</span>
                                        </div>

                                        <div class="flex justify-between items-center pt-2">
                                            <div class="flex items-center gap-2">
                                                <Rating :modelValue="4.5" readonly :stars="5" :cancel="false" class="text-[10px]" />
                                                <span class="text-[10px] font-bold text-slate-400">(240)</span>
                                            </div>
                                            <span class="text-lg font-black text-slate-900">{{ formatPrice(course.price) }}</span>
                                        </div>

                                        <div class="pt-2">
                                            <Button v-if="!props.enrolledIds?.includes(course.id)"
                                                label="Ajouter au panier" icon="pi pi-shopping-cart" @click="addToCart(course)"
                                                class="w-full !rounded-xl !bg-[#F1F5F9] !text-[#0F172A] hover:!bg-violet-600 hover:!text-white !border-none !text-[10px] !font-black !uppercase !tracking-widest !py-3.5 transition-all" />
                                            <Link v-else :href="route('student.my-courses')" class="w-full">
                                                <Button label="Reprendre le cours" icon="pi pi-play" class="w-full !rounded-xl !bg-emerald-500 !border-none !text-[10px] !font-black !uppercase !tracking-widest !py-3.5" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="props.courses?.last_page > 1" class="flex justify-center mt-16">
                                <nav class="flex gap-2 p-2 bg-white rounded-2xl shadow-sm border border-slate-100">
                                    <Link v-for="link in props.courses.links" :key="link.label" :href="link.url"
                                        :class="['px-4 py-2 rounded-xl text-xs font-black transition-all',
                                            link.active ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' : 'hover:bg-slate-50 text-slate-500']"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-select) {
    border: 1px solid transparent;
    transition: all 0.3s ease;
}
:deep(.p-select:hover) {
    border-color: #8B5CF6;
}
:deep(.p-rating-item-active .p-rating-icon) {
    color: #F59E0B;
}
</style>
