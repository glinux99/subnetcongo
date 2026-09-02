<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import InputNumber from 'primevue/inputnumber';

const props = defineProps({
    course: Object,
    relatedCourses: Array,
    reviews: Array,
    isEnrolled: Boolean,
    isInWishlist: Boolean,
    averageRating: [Number, String],
});

const formatPrice = (price) => {
    if (!price) return 'Gratuit';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(price);
};

const enroll = () => {
    if (props.isEnrolled) return;
    router.post(route('marketplace.cart.add', props.course.id), {}, { preserveScroll: true });
};
const toggleWishlist = () => {
    router.post(route('marketplace.wishlist.toggle', props.course.id), {}, { preserveScroll: true });
};

const getImageUrl = (path) => path?.startsWith('http') ? path : (path ? `/storage/${path}` : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=800');
</script>

<template>
    <AppLayout>
        <Head :title="course.title" />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <!-- HEADER GLOBAL -->
            <div class="bg-slate-900 pt-8 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-violet-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>

                <div class="max-w-[1600px] mx-auto relative z-10">
                    <div class="flex items-center gap-2 mb-4 text-xs">
                        <Link :href="route('marketplace.index')" class="text-slate-400 hover:text-white">Marketplace</Link>
                        <i class="pi pi-angle-right text-[10px] text-slate-500"></i>
                        <span class="text-slate-300">{{ course.category?.name }}</span>
                    </div>
                    <div class="flex items-center gap-3 mb-3 flex-wrap">
                        <h1 class="text-3xl font-black text-white tracking-tight">{{ course.title }}</h1>
                        <Tag :value="course.level || 'Tous niveaux'" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest capitalize" />
                    </div>
                    <p class="text-slate-300 text-base leading-relaxed mb-6 max-w-3xl">{{ course.short_description }}</p>
                    <div class="flex items-center gap-6 text-sm flex-wrap">
                        <div class="flex items-center gap-2">
                            <Rating :modelValue="Number(averageRating) || 4.5" readonly :stars="5" />
                            <span class="font-black text-white">{{ Number(averageRating) || '4.5' }}</span>
                            <span class="text-slate-400">({{ reviews?.length ?? 0 }} avis)</span>
                        </div>
                        <span class="text-slate-400">·</span>
                        <span class="text-slate-300 font-medium">{{ course.enrollments_count ?? 0 }} apprenants</span>
                    </div>
                    <div class="flex items-center gap-3 mt-6">
                        <Avatar :label="course.instructor?.name?.[0]" :image="course.instructor?.avatar_url" shape="circle" class="!bg-indigo-500 !text-white !w-10 !h-10" />
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Instructeur</p>
                            <p class="font-black text-white text-sm">{{ course.instructor?.name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[600px]">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Description & Curriculum -->
                        <main class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-7">
                                <h2 class="text-xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-info-circle text-lg"></i></div>
                                    À propos de ce cours
                                </h2>
                                <div class="prose prose-sm max-w-none text-slate-600 leading-relaxed" v-html="course.description"></div>
                            </div>

                            <div v-if="course.modules?.length" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-7">
                                <h2 class="text-xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center"><i class="pi pi-list text-lg"></i></div>
                                    Programme
                                </h2>
                                <div class="space-y-2">
                                    <div v-for="(module, i) in course.modules" :key="module.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center font-black text-xs shrink-0">{{ i + 1 }}</span>
                                            <div class="min-w-0">
                                                <p class="font-black text-sm text-slate-800">{{ module.title }}</p>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ module.lessons_count ?? 0 }} leçons</p>
                                            </div>
                                        </div>
                                        <i class="pi pi-lock text-slate-300"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-7">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-xl font-black text-slate-800 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center"><i class="pi pi-star text-lg"></i></div>
                                        Avis des apprenants
                                    </h2>
                                    <Rating :modelValue="Number(averageRating) || 0" readonly :stars="5" />
                                </div>
                                <div v-if="!reviews?.length" class="text-center py-10 text-slate-400 text-sm">Aucun avis pour le moment.</div>
                                <div v-else class="space-y-4">
                                    <div v-for="review in reviews" :key="review.id" class="border-b border-slate-100 pb-4 last:border-0">
                                        <div class="flex items-center gap-3 mb-2">
                                            <Avatar :label="review.user?.name?.[0]" shape="circle" class="!bg-gradient-to-br !from-violet-500 !to-indigo-500 !text-white !w-9 !h-9 !text-xs !font-black" />
                                            <div>
                                                <p class="font-black text-sm">{{ review.user?.name }}</p>
                                                <Rating :modelValue="review.rating" readonly :stars="5" class="text-xs" />
                                            </div>
                                        </div>
                                        <p class="text-sm text-slate-600">{{ review.comment }}</p>
                                    </div>
                                </div>
                            </div>
                        </main>

                        <!-- Sidebar Achat -->
                        <aside class="lg:col-span-1">
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-xl p-6 sticky top-6">
                                <img :src="getImageUrl(course.thumbnail_path || course.image)" class="w-full rounded-2xl mb-5 shadow-lg" />
                                <div class="text-center mb-5">
                                    <p class="text-5xl font-black text-violet-600 font-mono">{{ formatPrice(course.effective_price ?? course.price) }}</p>
                                    <p v-if="course.discount_price" class="text-sm text-slate-400 line-through mt-1">{{ formatPrice(course.price) }}</p>
                                </div>
                                <div class="space-y-3 mb-5 pb-5 border-b border-slate-100">
                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                        <div class="w-8 h-8 bg-violet-50 text-violet-600 rounded-lg flex items-center justify-center"><i class="pi pi-clock text-xs"></i></div>
                                        <span class="font-bold">{{ course.duration_hours ?? 0 }}h de contenu</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                        <div class="w-8 h-8 bg-violet-50 text-violet-600 rounded-lg flex items-center justify-center"><i class="pi pi-book text-xs"></i></div>
                                        <span class="font-bold">{{ course.lessons_count ?? 0 }} leçons</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                        <div class="w-8 h-8 bg-violet-50 text-violet-600 rounded-lg flex items-center justify-center"><i class="pi pi-globe text-xs"></i></div>
                                        <span class="font-bold">{{ course.language ?? 'Français' }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                        <div class="w-8 h-8 bg-violet-50 text-violet-600 rounded-lg flex items-center justify-center"><i class="pi pi-verified text-xs"></i></div>
                                        <span class="font-bold">Certificat inclus</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Button v-if="!isEnrolled" label="Ajouter au panier" icon="pi pi-shopping-cart" @click="enroll" class="!bg-violet-600 hover:!bg-violet-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                                    <Link v-else :href="route('student.my-courses')">
                                        <Button label="Accéder au cours" icon="pi pi-play" class="!bg-emerald-600 hover:!bg-emerald-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                                    </Link>
                                    <Button :label="isInWishlist ? 'Retirer des favoris' : 'Ajouter aux favoris'" :icon="isInWishlist ? 'pi pi-heart-fill' : 'pi pi-heart'" @click="toggleWishlist" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                                </div>
                                <div class="mt-5 p-4 bg-slate-50 rounded-2xl text-center">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Garantie</p>
                                    <p class="text-xs text-slate-600 font-bold">30 jours satisfait ou remboursé</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>

            <!-- Related -->
            <section v-if="relatedCourses?.length" class="max-w-[1600px] mx-auto px-6 mt-8">
                <h2 class="text-2xl font-black text-slate-800 mb-6">Cours similaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <Link v-for="rc in relatedCourses" :key="rc.id" :href="route('marketplace.show', rc.id)" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-violet-300 transition-all group">
                        <img :src="getImageUrl(rc.thumbnail_path || rc.image)" class="h-32 w-full object-cover group-hover:scale-105 transition" />
                        <div class="p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ rc.instructor?.name }}</p>
                            <h4 class="text-sm font-black text-slate-800 line-clamp-2 mb-2 tracking-tight">{{ rc.title }}</h4>
                            <p class="text-base font-black text-violet-600 font-mono">{{ formatPrice(rc.effective_price ?? rc.price) }}</p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>