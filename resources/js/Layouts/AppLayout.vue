<template>
  <div class="min-h-screen bg-[#FDFFFF] text-slate-900 font-sans selection:bg-red-100 selection:text-red-600 overflow-x-hidden">

    <!-- --- [SECTION 1] NAVIGATION COMPLEXE & MOBILE DRAWER --- -->
    <nav :class="['fixed w-full z-[100] transition-all duration-700 px-6 py-3', scrolled ? 'bg-white/95 backdrop-blur-3xl shadow-2xl border-b border-slate-100 py-2' : 'bg-transparent']">
      <div class="max-w-[1500px] mx-auto flex justify-between items-center">

        <!-- Logo & Burger Combo -->
        <div class="flex items-center gap-4">
          <button @click="isMenuOpen = true" class="lg:hidden flex items-center justify-center w-12 h-12 rounded-lg bg-[#003399] text-white shadow-lg active:scale-90 transition-all">
            <i class="pi pi-bars text-xl"></i>
          </button>

          <Link href="/" class="flex items-center gap-3 group cursor-pointer">
            <div class="relative overflow-hidden rounded-lg transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110 shadow-lg bg-white p-1">
              <img src="https://subnetcongo.net/assets/media/logos/logo.png" alt="SubnetCongo" class="h-10 md:h-14 w-auto object-contain" />
            </div>
          </Link>
        </div>

        <!-- Menu Desktop -->
        <div class="hidden lg:flex items-center gap-1 bg-slate-100 p-1.5 rounded-lg border border-white/50 shadow-inner">
          <template v-for="item in navLinks" :key="item.name">
            <div class="relative group">
              <Link :href="item.href" class="px-6 py-2.5 rounded-[1.5rem] text-sm font-black text-slate-600 hover:text-[#003399] hover:bg-white transition-all duration-300 flex items-center gap-2">
                {{ item.name }}
                <i v-if="item.submenu" class="pi pi-chevron-down text-[10px] group-hover:rotate-180 transition-transform"></i>
              </Link>

              <!-- Mega Menu Dropdown -->
              <div v-if="item.submenu" class="absolute top-full left-0 mt-4 w-[250px] bg-white rounded-3xl shadow-2xl border border-slate-100 p-4 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-500">
                <div v-for="sub in item.submenu" :key="sub" class="p-3 hover:bg-slate-50 rounded-xl transition-colors cursor-pointer">
                  <p class="text-sm font-bold text-slate-800">{{ sub }}</p>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Actions de Navigation -->
        <div class="flex items-center gap-3">
          <div class="hidden xl:flex flex-col items-end mr-4 text-right">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Besoin d'aide ?</span>
            <span :class="['text-sm font-black leading-none transition-colors', scrolled ? 'text-[#003399]' : 'text-white']">+243 973 359 648</span>
          </div>

          <button @click="goToClient" class="hidden md:flex items-center gap-2 px-5 py-3 text-sm font-black text-[#003399] bg-white/90 hover:bg-white rounded-lg transition-all shadow-sm">
            <i class="pi pi-user-circle"></i> Espace Client
          </button>

          <button @click="goToLogin" class="relative group overflow-hidden bg-[#EE1C25] text-white px-8 py-4 rounded-lg font-black shadow-xl transition-all hover:scale-105 active:scale-95">
            <span class="relative z-10 flex items-center gap-2">Académie <i class="pi pi-arrow-right"></i></span>
            <div class="absolute inset-0 bg-[#003399] translate-x-[-100%] group-hover:translate-x-0 transition-transform duration-500"></div>
          </button>
        </div>
      </div>
    </nav>

    <!-- --- MOBILE DRAWER --- -->
    <div class="fixed inset-0 z-[150] pointer-events-none">
        <transition name="fade">
            <div v-if="isMenuOpen" @click="isMenuOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-md pointer-events-auto"></div>
        </transition>

        <transition name="drawer-left">
            <div v-if="isMenuOpen" class="absolute left-0 top-0 h-full w-[300px] sm:w-[400px] bg-white shadow-2xl pointer-events-auto flex flex-col">
                <div class="p-8 flex justify-between items-center border-b border-slate-50 bg-slate-50/50">
                    <img src="https://subnetcongo.net/assets/media/logos/logo.png" class="h-10 w-auto" />
                    <button @click="isMenuOpen = false" class="w-12 h-12 flex items-center justify-center rounded-lg bg-white text-slate-400 hover:text-red-500 shadow-sm transition-all border border-slate-100">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-4 custom-scrollbar">
                    <div v-for="(item, idx) in navLinks" :key="item.name">
                        <Link :href="item.href" @click="isMenuOpen = false"
                           class="flex items-center justify-between p-5 rounded-[1.5rem] bg-slate-50 hover:bg-[#003399] group transition-all duration-300">
                            <span class="font-black text-slate-700 group-hover:text-white flex items-center gap-3">
                                <i :class="['pi text-lg', item.icon || 'pi-circle-fill text-[8px]']"></i>
                                {{ item.name }}
                            </span>
                            <i class="pi pi-chevron-right text-[10px] text-slate-300 group-hover:text-white transition-all"></i>
                        </Link>
                    </div>
                </div>

                <div class="p-8 bg-slate-900 text-white">
                    <div class="flex justify-between items-center mb-6">
                        <a v-for="s in ['facebook', 'linkedin', 'twitter', 'whatsapp']" :key="s" href="#" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center hover:bg-red-500 transition-all">
                            <i :class="`pi pi-${s}`"></i>
                        </a>
                    </div>
                    <p class="text-[10px] text-center font-bold text-slate-500 uppercase tracking-widest">© 2026 SubnetCongo Tech</p>
                </div>
            </div>
        </transition>
    </div>

    <!-- --- [MAIN CONTENT SLOT] --- -->
    <main class="min-h-[60vh]">
        <slot />
    </main>

    <!-- --- [SECTION 9] FOOTER RICHE --- -->
<footer class="bg-[#050811] text-slate-400 py-12 px-6 lg:px-20 font-sans tracking-tight">
    <div class="max-w-[1400px] mx-auto">

        <!-- SECTION 1: Newsletter Banner (Top) -->
        <div class="mb-16 p-8 lg:p-12 rounded-xl border border-white/5 bg-gradient-to-r from-white/[0.02] to-transparent flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="max-w-2xl">
                <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
                    Restez à la pointe de la <span class="text-[#E11D48] italic">Tech en RDC.</span>
                </h2>
                <p class="text-slate-400 text-lg">
                    Rejoignez notre communauté pour recevoir les alertes Starlink et nos bourses d'études.
                </p>
            </div>
            <div class="w-full lg:w-auto flex items-center bg-[#0A0F1C] border border-white/10 rounded-xl p-1.5 focus-within:border-red-500/50 transition-all">
                <input type="email" placeholder="votre@email.com" class="bg-transparent border-none outline-none px-4 py-3 text-white w-full lg:w-64 placeholder:text-slate-600 text-sm" />
                <button class="bg-[#E11D48] hover:bg-[#BE123C] text-white font-bold px-8 py-3 rounded-lg transition-all active:scale-95 whitespace-nowrap">
                    S'abonner
                </button>
            </div>
        </div>

        <!-- SECTION 2: Grid Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 mb-20">

            <!-- Branding -->
            <div class="lg:col-span-3 space-y-8">
                <div class="flex flex-col gap-1">
                    <h1 class="text-xl font-black text-white italic tracking-tighter">
                        SubNet<span class="text-[#E11D48]">Congo</span>
                    </h1>
                    <span class="text-[10px] text-slate-500 uppercase tracking-[0.3em]">Technologie du futur</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs italic font-medium">
                    {{ identity.site_tagline || "Propulser l'excellence numérique au cœur de la République Démocratique du Congo." }}
                </p>
                <!-- Socials -->
                <div class="flex gap-3 mt-8">
                    <a v-for="soc in [
                        { icon: 'facebook', link: identity.social_facebook, color: 'hover:bg-blue-600' },
                        { icon: 'twitter', link: identity.social_twitter, color: 'hover:bg-black' },
                        { icon: 'linkedin', link: identity.social_linkedin, color: 'hover:bg-blue-700' },
                        { icon: 'youtube', link: identity.social_youtube, color: 'hover:bg-red-600' }
                    ]" :key="soc.icon" :href="soc.link"
                       class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_10px_20px_rgba(0,0,0,0.4)]"
                       :class="soc.color">
                        <i :class="`pi pi-${soc.icon} text-sm  text-white`"></i>
                    </a>
                </div>
            </div>

            <!-- Col 2: Solutions -->
            <div class="lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-[0.2em] text-white mb-8 relative inline-block">
                    Solutions
                    <span class="absolute -bottom-2 left-0 w-6 h-[2px] bg-[#E11D48]"></span>
                </h4>
                <ul class="space-y-4 text-sm font-medium">
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Internet Satellite
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Starlink Business
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Fibre Optique
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Audit Réseau
                    </li>
                </ul>
            </div>

            <!-- Col 3: Académie -->
            <div class="lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-[0.2em] text-white mb-8 relative inline-block">
                    Académie
                    <span class="absolute -bottom-2 left-0 w-6 h-[2px] bg-[#E11D48]"></span>
                </h4>
                <ul class="space-y-4 text-sm font-medium">
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Catalogue Cours
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Espace Étudiant
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Certifications
                    </li>
                    <li class="flex items-center gap-2 group cursor-pointer hover:text-white transition-colors">
                        <span class="w-1 h-1 rounded-full bg-slate-700 group-hover:bg-[#E11D48]"></span> Recrutement
                    </li>
                </ul>
            </div>

            <!-- Col 4: Starlink Authorized Card -->
            <div class="lg:col-span-5 lg:flex lg:justify-end">
                <div class="w-full max-w-sm p-8 rounded-3xl bg-[#0A0F1C] border border-white/5 shadow-2xl space-y-6">
                    <div class="flex items-center gap-4 border-b border-white/5 pb-6">
                        <div class="w-10 h-10 rounded-full border border-red-500/50 flex items-center justify-center text-[#E11D48]">
                            <i class="pi pi-verified text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">STARLINK</p>
                            <p class="text-sm font-bold text-white tracking-tight">Authorized Reseller</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h5 class="text-xs font-black uppercase italic tracking-widest text-white">NORD-KIVU/GOMA</h5>
                        <p class="text-xs leading-relaxed text-slate-500 font-medium">
                           {{ legal?.address_line || identity.contact_address }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Bottom Bar -->
        <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">
                  {{ identity.footer_text || `© ${year} ${identity.site_name}. ALL SYSTEMS OPERATIONAL.` }} DESIGN BY LINUX99
            </p>
            <div class="flex gap-8 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">
                <Link :href="route('confidentialite')" class="hover:text-white transition-colors">Confidentialité</Link>
                <Link :href="route('mentions-legales')" class="hover:text-white transition-colors">Mentions Légales</Link>
            </div>
        </div>
    </div>
</footer>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

// Données globales partagées par HandleInertiaRequests (toujours présentes,
// mais on protège contre un premier rendu où usePage() pourrait être vide).
const page = usePage();
const identity = computed(() => page.props.identity || {});
const legal    = computed(() => page.props.legal    || null);
const menuPages = computed(() => Array.isArray(page.props.menuPages) ? page.props.menuPages : []);

// États
const scrolled = ref(false);
const isMenuOpen = ref(false);

// Navigation
const navLinks = [
    { name: 'Accueil', href: route('landing'), icon: 'pi-home' },
    { name: 'À Propos', href: route('about'), icon: 'pi-info-circle' },
    { name: 'Réalisations', href: route('realizations'), icon: 'pi-info-circle' },
    { name: 'Academy', href: route('academy'), icon: 'pi-graduation-cap' },
    { name: 'Blog', href: route('blog'), icon: 'pi-book' },
    { name: 'Contact', href: route('contact'), icon: 'pi-envelope' }
];

const footerLinks = [
    { title: "Solutions", links: ["Internet Satellite", "Starlink Business", "Fibre Optique", "Audit Réseau"] },
    { title: "Académie", links: ["Catalogue Cours", "Espace Étudiant", "Certifications", "Recrutement"] }
];

// Logique
const handleScroll = () => { scrolled.value = window.scrollY > 50; };
const goToLogin = () => { router.get('/login'); };
const goToClient =()=>{
    router.get('/contact');
};
onMounted(() => { window.addEventListener('scroll', handleScroll); });
onUnmounted(() => { window.removeEventListener('scroll', handleScroll); });
</script>

<style>
/* Animations globalisées pour le layout */
.drawer-left-enter-active, .drawer-left-leave-active { transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-left-enter-from, .drawer-left-leave-to { transform: translateX(-100%); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.4s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #003399; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #EE1C25; }
</style>
