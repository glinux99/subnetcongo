<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';

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
</script>

<template>
    <AppLayout>
        <Head :title="course.title" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans pb-20">
            <!-- Hero -->
            <section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-12 px-6">
                <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-4 text-xs">
                            <Link :href="route('marketplace.index')" class="text-slate-400 hover:text-white">Marketplace</Link>
                            <i class="pi pi-angle-right text-[10px] text-slate-500"></i>
                            <span class="text-slate-300">{{ course.category?.name }}</span>
                        </div>
                        <h1 class="text-4xl font-black italic tracking-tight mb-4">{{ course.title }}</h1>
                        <p class="text-slate-300 text-base leading-relaxed mb-6">{{ course.short_description }}</p>
                        <div class="flex items-center gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <Rating :modelValue="Number(averageRating) || 4.5" readonly :stars="5" />
                                <span class="font-black">{{ Number(averageRating) || '4.5' }}</span>
                                <span class="text-slate-400">({{ reviews?.length ?? 0 }} avis)</span>
                            </div>
                            <span class="text-slate-400">· {{ course.enrollments_count ?? 0 }} apprenants</span>
                            <Tag v-if="course.level" :value="course.level" severity="info" class="capitalize text-[9px] font-black" />
                        </div>
                        <div class="flex items-center gap-3 mt-6">
                            <img :src="course.instructor?.avatar_url || `https://ui-avatars.com/api/?name=${course.instructor?.name}&background=6366f1&color=fff`"
                                 class="w-10 h-10 rounded-full" />
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Instructeur</p>
                                <p class="font-black text-sm">{{ course.instructor?.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="max-w-[1400px] mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Description & Curriculum -->
                <main class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
                        <h2 class="text-xl font-black italic text-slate-800 mb-4">À propos de ce cours</h2>
                        <div class="prose prose-sm max-w-none text-slate-600 leading-relaxed" v-html="course.description"></div>
                    </div>

                    <div v-if="course.modules?.length" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
                        <h2 class="text-xl font-black italic text-slate-800 mb-4">Programme</h2>
                        <div class="space-y-2">
                            <div v-for="(module, i) in course.modules" :key="module.id"
                                 class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center font-black text-xs">
                                        {{ i + 1 }}
                                    </span>
                                    <div>
                                        <p class="font-black text-sm text-slate-800">{{ module.title }}</p>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                            {{ module.lessons_count ?? 0 }} leçons
                                        </p>
                                    </div>
                                </div>
                                <i class="pi pi-lock text-slate-300"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-black italic text-slate-800">Avis des apprenants</h2>
                            <Rating :modelValue="Number(averageRating) || 0" readonly :stars="5" />
                        </div>
                        <div v-if="!reviews?.length" class="text-center py-10 text-slate-400 text-sm">
                            Aucun avis pour le moment. Soyez le premier à partager votre expérience.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="review in reviews" :key="review.id" class="border-b border-slate-100 pb-4 last:border-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 flex items-center justify-center text-white font-black text-xs">
                                        {{ review.user?.name?.[0] ?? '?' }}
                                    </div>
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
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xl p-6 sticky top-6">
                        <div class="text-center mb-6 pb-6 border-b border-slate-100">
                            <p class="text-5xl font-black text-violet-600 italic">{{ formatPrice(course.effective_price ?? course.price) }}</p>
                            <p v-if="course.discount_price" class="text-sm text-slate-400 line-through mt-1">
                                {{ formatPrice(course.price) }}
                            </p>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-slate-600">
                                <i class="pi pi-clock text-violet-500"></i>
                                <span>{{ course.duration_hours ?? 0 }}h de contenu</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600">
                                <i class="pi pi-book text-violet-500"></i>
                                <span>{{ course.lessons_count ?? 0 }} leçons</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600">
                                <i class="pi pi-globe text-violet-500"></i>
                                <span>{{ course.language ?? 'Français' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600">
                                <i class="pi pi-verified text-violet-500"></i>
                                <span>Certificat inclus</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Button v-if="!isEnrolled" label="Ajouter au panier" icon="pi pi-shopping-cart"
                                    @click="enroll" class="p-button-primary w-full font-black py-3" />
                            <Link v-else :href="route('student.my-courses')">
                                <Button label="Accéder au cours" icon="pi pi-play" class="p-button-success w-full font-black py-3" />
                            </Link>
                            <Button :label="isInWishlist ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                                    :icon="isInWishlist ? 'pi pi-heart-fill' : 'pi pi-heart'"
                                    @click="toggleWishlist" outlined severity="secondary"
                                    class="w-full font-bold" />
                        </div>

                        <div class="mt-6 p-4 bg-slate-50 rounded-xl text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Garantie</p>
                            <p class="text-xs text-slate-600">30 jours satisfait ou remboursé</p>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Related -->
            <section v-if="relatedCourses?.length" class="max-w-[1400px] mx-auto px-6 mt-8">
                <h2 class="text-2xl font-black italic text-slate-800 mb-6">Cours similaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <Link v-for="rc in relatedCourses" :key="rc.id" :href="route('marketplace.show', rc.id)"
                          class="bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-lg transition group">
                        <img :src="rc.thumbnail_path || rc.image || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=400'"
                             class="h-32 w-full object-cover group-hover:scale-105 transition" />
                        <div class="p-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ rc.instructor?.name }}</p>
                            <h4 class="text-sm font-black text-slate-800 line-clamp-2 mb-2">{{ rc.title }}</h4>
                            <p class="text-base font-black text-violet-600">{{ formatPrice(rc.effective_price ?? rc.price) }}</p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>