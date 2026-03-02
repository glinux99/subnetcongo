<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

/**
 * --- ÉTATS & FILTRES ---
 */
const searchQuery = ref('');
const selectedCategory = ref('Tous');
const activeTab = ref(0);

const categories = ['Tous', 'Réseaux', 'Cybersécurité', 'Cloud', 'Télécoms', 'Développement'];

/**
 * --- DATA : LES COURS (ACADEMY CATALOG) ---
 */
const courses = ref([
    {
        id: 1,
        title: "Expert en Administration Cisco CCNA 200-301",
        category: "Réseaux",
        level: "Débutant à Intermédiaire",
        duration: "120 Heures",
        instructor: "Eng. Marc Luvambo",
        rating: 4.9,
        students: 1250,
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        isLive: true,
        price: "Gratuit (Bourse)",
        progress: 0,
        desc: "Maîtrisez les fondamentaux du routage et de la commutation sur des équipements réels."
    },
    {
        id: 2,
        title: "Ethical Hacking & Défense des Réseaux",
        category: "Cybersécurité",
        level: "Avancé",
        duration: "85 Heures",
        instructor: "Sarah Kahindo",
        rating: 5.0,
        students: 840,
        image: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        isLive: false,
        price: "Premium",
        progress: 0,
        desc: "Apprenez à identifier les vulnérabilités et à sécuriser les infrastructures critiques congolaises."
    },
    {
        id: 3,
        title: "Ingénierie Fibre Optique & Soudure",
        category: "Télécoms",
        level: "Technique",
        duration: "45 Heures",
        instructor: "Jean-Paul M.",
        rating: 4.8,
        students: 600,
        image: "https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070",
        isLive: true,
        price: "Présentiel",
        progress: 0,
        desc: "Formation pratique sur le déploiement de réseaux FTTH et la maintenance des câbles sous-marins."
    },
    {
        id: 4,
        title: "Architecture Cloud AWS & Azure",
        category: "Cloud",
        level: "Intermédiaire",
        duration: "90 Heures",
        instructor: "Ali Bamba",
        rating: 4.7,
        students: 450,
        image: "https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072",
        isLive: false,
        price: "Hybride",
        progress: 0,
        desc: "Déployez des solutions scalables et sécurisées dans le cloud pour les entreprises africaines."
    },
    {
        id: 5,
        title: "Installation & Maintenance Starlink Business",
        category: "Télécoms",
        level: "Technique",
        duration: "20 Heures",
        instructor: "Patient M.",
        rating: 4.9,
        students: 320,
        image: "https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?q=80&w=2072",
        isLive: true,
        price: "Certifiant",
        progress: 0,
        desc: "Devenez installateur agréé pour les solutions satellites Starlink en milieu rural."
    },
    {
        id: 6,
        title: "Développement Fullstack avec Vue.js & Laravel",
        category: "Développement",
        level: "Intermédiaire",
        duration: "150 Heures",
        instructor: "Linux99",
        rating: 4.9,
        students: 2100,
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=2072",
        isLive: false,
        price: "Premium",
        progress: 0,
        desc: "Créez des applications web modernes, performantes et sécurisées."
    }
]);

/**
 * --- DATA : LES ENSEIGNANTS (EXPERTS) ---
 */
const instructors = [
    {
        name: "Eng. Marc Luvambo",
        role: "Lead Network Architect",
        bio: "Expert Cisco CCIE avec 15 ans d'expérience dans les backbones télécoms.",
        avatar: "https://i.pravatar.cc/150?u=marc",
        courses: 12,
        linkedIn: "#"
    },
    {
        name: "Sarah Kahindo",
        role: "Cybersecurity Analyst",
        bio: "Spécialiste en audit de sécurité et pentesting pour les institutions bancaires.",
        avatar: "https://i.pravatar.cc/150?u=sarah",
        courses: 5,
        linkedIn: "#"
    },
    {
        name: "Patient M.",
        role: "CEO & Satellite Expert",
        bio: "Pionnier du VSAT en RDC, certifié par les plus grands opérateurs satellites.",
        avatar: "https://i.pravatar.cc/150?u=patient",
        courses: 3,
        linkedIn: "#"
    }
];

/**
 * --- DATA : BIBLIOTHÈQUE DE FICHIERS ---
 */
const resources = [
    { title: "Guide de l'étudiant Subnet 2026", type: "PDF", size: "2.4 MB", date: "12 Fév 2026" },
    { title: "Logiciel Cisco Packet Tracer v8.2", type: "EXE", size: "145 MB", date: "10 Jan 2026" },
    { title: "Syllabus Cybersécurité Avancée", type: "PDF", size: "1.1 MB", date: "05 Fév 2026" },
    { title: "Outils de calcul de signal VSAT", type: "ZIP", size: "15 MB", date: "20 Déc 2025" }
];

/**
 * --- LOGIQUE DE RECHERCHE ---
 */
const filteredCourses = computed(() => {
    return courses.value.filter(course => {
        const matchesSearch = course.title.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesCat = selectedCategory.value === 'Tous' || course.category === selectedCategory.value;
        return matchesSearch && matchesCat;
    });
});

const getSeverity = (price) => {
    switch (price) {
        case 'Gratuit (Bourse)': return 'success';
        case 'Premium': return 'danger';
        case 'Présentiel': return 'info';
        default: return 'warning';
    }
};
</script>

<template>
    <Head title="Subnet Academy - Formations d'Élite" />

    <AppLayout>
        <!-- --- [SECTION 1] HERO ACADÉMIQUE --- -->
        <header class="relative min-h-[70vh] flex items-center pt-32 pb-20 bg-[#003399] overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070" class="w-full h-full object-cover opacity-20 scale-105 animate-slow-zoom" />
                <div class="absolute inset-0 bg-gradient-to-b from-[#003399] via-[#003399]/80 to-[#FDFFFF]"></div>
            </div>

            <div class="relative z-10 max-w-[1500px] mx-auto px-6 w-full">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                    <div class="space-y-10">
                        <div class="inline-flex items-center gap-4 px-6 py-2 bg-white/10 backdrop-blur-3xl border border-white/20 rounded-full text-white">
                            <span class="text-[10px] font-black uppercase tracking-[0.4em]">Propulsez votre carrière</span>
                        </div>
                        <h1 class="text-6xl md:text-8xl font-black text-white leading-tight tracking-tighter italic">
                            L'Élite de la <span class="text-red-500 underline underline-offset-8">Tech</span> <br/> au Congo.
                        </h1>
                        <p class="text-2xl text-blue-100/80 font-medium leading-relaxed max-w-xl italic">
                            Apprenez avec les ingénieurs qui bâtissent l'infrastructure numérique de la RDC. Certifications internationales garanties.
                        </p>
                        <div class="flex gap-10">
                            <div>
                                <p class="text-4xl font-black text-white italic leading-none">15K+</p>
                                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mt-2">Certifiés</p>
                            </div>
                            <div class="w-px h-12 bg-white/20"></div>
                            <div>
                                <p class="text-4xl font-black text-white italic leading-none">92%</p>
                                <p class="text-xs font-bold text-blue-200 uppercase tracking-widest mt-2">Réussite</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Card Hero -->
                    <div class="hidden lg:block relative">
                        <div class="bg-white/10 backdrop-blur-3xl p-8 rounded-lg border border-white/20 shadow-2xl rotate-0">
                            <div class="flex items-center gap-6 mb-8">
                                <div class="w-20 h-20 bg-red-600 rounded-2xl flex items-center justify-center text-white shadow-xl">
                                    <i class="pi pi-bolt text-4xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-white text-2xl font-black italic">Prochaine Session Live</h4>
                                    <p class="text-blue-100 text-sm">Cisco CCNA - Module Routage Avancé</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between text-white text-xs font-black uppercase">
                                    <span>Places restantes</span>
                                    <span>05 / 20</span>
                                </div>
                                <ProgressBar :value="75" class="subnet-progress" :showValue="false"></ProgressBar>
                            </div>
                            <button class="w-full mt-8 py-5 bg-white text-[#003399] rounded-2xl font-black text-lg hover:bg-red-500 hover:text-white transition-all shadow-xl">S'inscrire maintenant</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- --- [SECTION 2] RECHERCHE & FILTRES --- -->
        <section class="py-20 -mt-10 relative z-30 px-6">
            <div class="max-w-[1500px] mx-auto bg-white rounded-lg p-8 md:p-12 shadow-2xl border border-slate-100">
                <div class="flex flex-col lg:flex-row gap-8 items-center">
                    <div class="flex-1 w-full relative">
                        <i class="pi pi-search absolute left-8 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText v-model="searchQuery" placeholder="Quelle compétence souhaitez-vous acquérir ?" class="academy-search-input" />
                    </div>
                    <div class="flex flex-wrap justify-center gap-3">
                        <button v-for="cat in categories" :key="cat"
                                @click="selectedCategory = cat"
                                :class="['px-8 py-4 rounded-2xl font-black text-sm transition-all', selectedCategory === cat ? 'bg-[#EE1C25] text-white shadow-xl shadow-red-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100']">
                            {{ cat }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 3] CATALOGUE DE COURS --- -->
        <section class="py-20 px-6">
            <div class="max-w-[1500px] mx-auto">
                <div class="flex justify-between items-end mb-16">
                    <div class="space-y-4">
                        <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm italic">Catalogue</h2>
                        <h3 class="text-5xl font-black text-slate-900 tracking-tighter">Formations <span class="text-[#003399]">Disponibles</span>.</h3>
                    </div>
                    <p class="text-slate-400 font-bold italic">{{ filteredCourses.length }} formations trouvées</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div v-for="course in filteredCourses" :key="course.id" class="group bg-white rounded-lg border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-700 overflow-hidden flex flex-col h-full">
                        <div class="h-64 relative overflow-hidden">
                            <img :src="course.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                            <div class="absolute top-6 left-6 flex gap-2">
                                <Tag :severity="getSeverity(course.price)" :value="course.price" class="subnet-tag" />
                                <div v-if="course.isLive" class="px-4 py-1.5 bg-red-600 text-white text-[9px] font-black rounded-full flex items-center gap-2 animate-pulse uppercase tracking-widest shadow-lg">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full"></span> Live
                                </div>
                            </div>

                            <div class="absolute bottom-6 left-6 flex items-center gap-4">
                                <div class="flex items-center gap-2 text-white">
                                    <i class="pi pi-star-fill text-amber-400 text-xs"></i>
                                    <span class="text-xs font-black">{{ course.rating }}</span>
                                </div>
                                <div class="w-px h-3 bg-white/40"></div>
                                <div class="flex items-center gap-2 text-white text-xs font-bold italic">
                                    <i class="pi pi-users text-[10px]"></i>
                                    {{ course.students }} Apprenants
                                </div>
                            </div>
                        </div>

                        <div class="p-10 flex flex-col flex-1">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#003399] mb-4">{{ course.category }}</span>
                            <h4 class="text-2xl font-black text-slate-900 leading-tight mb-6 group-hover:text-red-600 transition-colors">{{ course.title }}</h4>
                            <p class="text-slate-500 font-medium text-sm leading-relaxed mb-8 italic">{{ course.desc }}</p>

                            <div class="mt-auto space-y-8">
                                <div class="flex items-center justify-between border-t border-slate-50 pt-6">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-clock text-slate-400"></i>
                                        <span class="text-xs font-black text-slate-500 italic">{{ course.duration }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-chart-bar text-slate-400"></i>
                                        <span class="text-xs font-black text-slate-500 italic">{{ course.level }}</span>
                                    </div>
                                </div>
                                <button class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-sm hover:bg-[#003399] transition-all shadow-xl flex items-center justify-center gap-3">
                                    Détails du Cursus <i class="pi pi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 4] ENSEIGNANTS (EXPERTS) --- -->
        <section class="py-32 bg-slate-900 text-white relative overflow-hidden">
            <div class="max-w-[1500px] mx-auto px-6 relative z-10">
                <div class="text-center mb-24 space-y-4">
                    <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm">L'Équipe Pédagogique</h2>
                    <h3 class="text-5xl font-black tracking-tighter italic">L'intelligence derrière <span class="text-blue-400 underline decoration-red-600 underline-offset-8">votre réussite</span>.</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div v-for="teacher in instructors" :key="teacher.name" class="group bg-white/5 border border-white/10 rounded-[3.5rem] p-12 hover:bg-white/10 transition-all text-center">
                        <div class="relative inline-block mb-10">
                            <img :src="teacher.avatar" class="w-32 h-32 rounded-lg border-4 border-white/20 shadow-2xl grayscale group-hover:grayscale-0 transition-all duration-700" />
                            <div class="absolute -bottom-3 -right-3 w-10 h-10 bg-[#EE1C25] rounded-xl flex items-center justify-center shadow-xl">
                                <i class="pi pi-verified text-white"></i>
                            </div>
                        </div>
                        <h4 class="text-3xl font-black italic mb-2">{{ teacher.name }}</h4>
                        <p class="text-red-500 font-black uppercase tracking-widest text-[10px] mb-6">{{ teacher.role }}</p>
                        <p class="text-slate-400 font-medium italic leading-relaxed mb-8">{{ teacher.bio }}</p>

                        <div class="flex items-center justify-center gap-8 border-t border-white/5 pt-8">
                            <div class="text-center">
                                <p class="text-xl font-black text-white italic leading-none">{{ teacher.courses }}</p>
                                <p class="text-[9px] font-black uppercase text-slate-500 mt-2">Cours</p>
                            </div>
                            <div class="w-px h-6 bg-white/10"></div>
                            <a :href="teacher.linkedIn" target="_blank" class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-slate-900 hover:bg-red-600 hover:text-white transition-all">
                                <i class="pi pi-linkedin text-xl"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decors -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-red-600/10 rounded-full blur-[150px]"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-600/10 rounded-full blur-[150px]"></div>
        </section>

        <!-- --- [SECTION 5] BIBLIOTHÈQUE & FICHIERS --- -->
        <section class="py-32 px-6">
            <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-20">
                <div class="lg:col-span-4 space-y-12">
                    <div class="space-y-6">
                        <h2 class="text-[#003399] font-black uppercase tracking-[0.5em] text-sm italic">Ressources</h2>
                        <h3 class="text-5xl font-black text-slate-900 tracking-tighter italic leading-none">Votre Bibliothèque <br/> de <span class="text-red-600 italic">Survie Tech</span>.</h3>
                        <p class="text-xl text-slate-500 font-medium italic leading-relaxed">Téléchargez les guides, les simulateurs et les documentations techniques indispensables pour vos sessions.</p>
                    </div>
                    <div class="bg-slate-900 p-12 rounded-[3.5rem] text-white shadow-2xl relative overflow-hidden group">
                        <h4 class="text-2xl font-black italic mb-4">Accès Étudiant</h4>
                        <p class="text-slate-400 font-medium italic mb-8">Votre tableau de bord personnel pour suivre vos certificats et cours.</p>
                        <button class="px-10 py-5 bg-[#EE1C25] text-white rounded-2xl font-black shadow-xl hover:scale-105 transition-all">Connexion Portail</button>
                        <i class="pi pi-folder-open absolute -right-6 -bottom-6 text-[10rem] text-white/5 group-hover:rotate-12 transition-transform"></i>
                    </div>
                </div>

                <div class="lg:col-span-8 space-y-6">
                    <div v-for="file in resources" :key="file.title" class="flex flex-col sm:flex-row items-center justify-between p-8 bg-slate-50 rounded-lg border border-slate-100 hover:bg-white hover:shadow-xl transition-all group">
                        <div class="flex items-center gap-8 mb-6 sm:mb-0 w-full sm:w-auto">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-red-600 shadow-lg group-hover:bg-red-600 group-hover:text-white transition-all">
                                <i :class="['pi text-2xl', file.type === 'PDF' ? 'pi-file-pdf' : 'pi-file']"></i>
                            </div>
                            <div>
                                <h5 class="text-xl font-black text-slate-900 italic mb-1">{{ file.title }}</h5>
                                <div class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    <span>{{ file.type }}</span>
                                    <span>•</span>
                                    <span>{{ file.size }}</span>
                                    <span class="hidden md:inline">•</span>
                                    <span class="hidden md:inline">{{ file.date }}</span>
                                </div>
                            </div>
                        </div>
                        <button class="w-full sm:w-auto px-10 py-5 bg-white border-2 border-slate-900 text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-900 hover:text-white transition-all flex items-center justify-center gap-4">
                            Télécharger <i class="pi pi-download"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 6] FAQ ACADEMY --- -->
        <section class="py-32 px-6 bg-slate-50">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-24 space-y-4">
                    <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm">FAQ</h2>
                    <h3 class="text-6xl font-black text-slate-900 tracking-tighter italic leading-none">Questions <br/> Fréquentes.</h3>
                </div>

                <div class="space-y-6">
                    <div v-for="i in 3" :key="i" class="bg-white rounded-lg p-10 border border-slate-100 shadow-sm hover:shadow-xl transition-all cursor-pointer group">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-2xl font-black text-slate-900 italic">Quels sont les prérequis pour le CCNA ?</h4>
                            <i class="pi pi-plus text-red-600 group-hover:rotate-45 transition-transform"></i>
                        </div>
                        <p class="text-slate-500 font-medium italic leading-relaxed border-l-2 border-slate-100 pl-6 group-hover:border-red-500 transition-colors">Aucun prérequis n'est nécessaire pour débuter, mais une base en informatique générale et un bon niveau d'anglais technique sont fortement recommandés pour réussir l'examen.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 7] CALL TO ACTION FINAL --- -->
        <section class="py-32 px-6 bg-white relative overflow-hidden">
            <div class="max-w-[1500px] mx-auto text-center relative z-10">
                <div class="mb-16">
                    <i class="pi pi-graduation-cap text-9xl text-red-600 opacity-10 inline-block mb-10"></i>
                    <h3 class="text-5xl md:text-[6.5rem] font-black text-slate-900 leading-[0.9] tracking-tighter italic">Prêt à dominer la <br/> <span class="text-[#003399]">Scène Tech Mondiale</span> ?</h3>
                </div>
                <div class="flex flex-wrap justify-center gap-8">
                    <button class="px-16 py-8 bg-slate-900 text-white rounded-3xl font-black text-xl hover:bg-[#EE1C25] transition-all shadow-2xl flex items-center gap-6 group">
                        Commencer ma Formation <i class="pi pi-bolt group-hover:translate-x-4 transition-transform text-red-500"></i>
                    </button>
                    <button class="px-16 py-8 bg-white border-4 border-slate-900 text-slate-900 rounded-3xl font-black text-xl hover:bg-slate-900 hover:text-white transition-all shadow-xl">
                        Voir le Programme Complet
                    </button>
                </div>
            </div>
        </section>

    </AppLayout>
</template>

<style scoped>
/* --- CUSTOM ACADEMY SEARCH --- */
.academy-search-input {
    width: 100% !important;
    background: #f8fafc !important;
    border: 2px solid #f1f5f9 !important;
    border-radius: 1.5rem !important;
    padding: 1.5rem 1.5rem 1.5rem 4rem !important;
    font-weight: 800 !important;
    font-size: 1.1rem !important;
    transition: all 0.3s ease !important;
    font-family: 'Inter', sans-serif;
}

.academy-search-input:focus {
    background: white !important;
    border-color: #EE1C25 !important;
    box-shadow: 0 20px 40px rgba(238, 28, 37, 0.08) !important;
    outline: none !important;
}

/* --- PROGRESS BAR CUSTOM --- */
:deep(.subnet-progress.p-progressbar) {
    height: 12px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border-radius: 20px !important;
    border: none !important;
}

:deep(.subnet-progress .p-progressbar-value) {
    background: #EE1C25 !important;
    border-radius: 20px !important;
}

/* --- TAG CUSTOM --- */
:deep(.subnet-tag.p-tag) {
    border-radius: 0.5rem !important;
    padding: 0.5rem 1rem !important;
    font-weight: 900 !important;
    font-size: 9px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.1em !important;
}

/* --- ANIMATIONS --- */
@keyframes slow-zoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.1); }
}
.animate-slow-zoom { animation: slow-zoom 25s linear infinite alternate; }

/* Scrollbar Style for long content */
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #003399; border-radius: 10px; border: 2px solid #f8fafc; }
::-webkit-scrollbar-thumb:hover { background: #EE1C25; }

/* Custom Text Shadow for Titles */
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>
