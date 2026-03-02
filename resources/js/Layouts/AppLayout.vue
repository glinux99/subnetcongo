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
            <span :class="['text-sm font-black leading-none transition-colors', scrolled ? 'text-[#003399]' : 'text-white']">+243 970 912 428</span>
          </div>

          <button class="hidden md:flex items-center gap-2 px-5 py-3 text-sm font-black text-[#003399] bg-white/90 hover:bg-white rounded-lg transition-all shadow-sm">
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
    <footer class="relative bg-[#0A0F1C] pt-24 pb-10 px-6 overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#EE1C25] to-transparent opacity-30"></div>

        <div class="max-w-[1400px] mx-auto relative z-10">
            <!-- Newsletter Card -->
            <div class="relative mb-20 p-8 rounded-lg bg-gradient-to-br from-[#161C2D] to-[#0A0F1C] border border-white/10 shadow-2xl group overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
                    <div class="text-center lg:text-left">
                        <h4 class="text-3xl font-black text-white mb-2 italic">Restez à la pointe de la <span class="text-[#EE1C25]">Tech en RDC</span>.</h4>
                        <p class="text-slate-400 font-medium">Rejoignez notre communauté pour recevoir les alertes Starlink et nos bourses d'études.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <input type="email" placeholder="votre@email.com" class="bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white outline-none focus:border-[#EE1C25] w-full sm:w-[300px]" />
                        <button class="bg-[#EE1C25] text-white px-10 py-5 rounded-2xl font-black hover:scale-105 transition-all">S'abonner</button>
                    </div>
                </div>
            </div>

            <!-- Grille Footer -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 mb-20">
                <div class="space-y-8">
                    <img src="https://subnetcongo.net/assets/media/logos/logo.png" class="h-12 w-auto brightness-200" />
                    <p class="text-slate-400 font-medium leading-relaxed italic">Leader panafricain basé à Goma, combinant infrastructure réseau et centre de formation d'élite.</p>
                    <div class="flex gap-3">
                        <a v-for="s in ['facebook', 'linkedin', 'instagram', 'whatsapp']" :key="s" href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-slate-300 hover:bg-[#003399] transition-all">
                            <i :class="`pi pi-${s}`"></i>
                        </a>
                    </div>
                </div>

                <div v-for="group in footerLinks" :key="group.title" class="space-y-6">
                    <h5 class="text-white font-black uppercase text-xs tracking-widest border-b border-red-500 w-fit pb-2">{{ group.title }}</h5>
                    <ul class="space-y-4">
                        <li v-for="link in group.links" :key="link">
                            <Link href="#" class="text-slate-400 font-bold hover:text-white transition-colors flex items-center gap-2 group">
                                <span class="w-1 h-1 bg-slate-700 rounded-full group-hover:bg-[#EE1C25]"></span> {{ link }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="space-y-8 bg-white/5 p-8 rounded-3xl border border-white/10">
                    <div class="flex items-center gap-4 text-white">
                        <i class="pi pi-verified text-2xl text-red-500"></i>
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-500">Starlink</p>
                            <p class="text-xs font-bold">Authorized Reseller</p>
                        </div>
                    </div>
                    <div class="text-sm">
                        <p class="text-white font-black mb-2 uppercase italic tracking-tighter">Nord-Kivu / Goma</p>
                        <p class="text-slate-500 font-medium text-xs leading-relaxed">N° 0001, Avenue Des Touristes, Quartier les Volcans, Goma, RDC</p>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-slate-500 font-black text-[10px] uppercase tracking-[0.3em]">© 2026 <span class="text-white">SUBNETCONGO</span>. Design by Linux99</p>
                <div class="flex gap-6 text-[9px] font-black uppercase tracking-widest text-slate-500">
                    <a href="#" class="hover:text-red-500 transition-colors">Confidentialité</a>
                    <a href="#" class="hover:text-red-500 transition-colors">Mentions Légales</a>
                </div>
            </div>
        </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

// États
const scrolled = ref(false);
const isMenuOpen = ref(false);

// Navigation
const navLinks = [
    { name: 'Accueil', href: route('landing'), icon: 'pi-home' },
    { name: 'À Propos', href: route('about'), icon: 'pi-info-circle' },
    // { name: 'Academy', href: '/academy', icon: 'pi-graduation-cap', submenu: ['Cisco CCNA', 'Cyber-Sécurité', 'Cloud Computing'] },
    { name: 'Academy', href: route('academy'), icon: 'pi-graduation-cap'},
    { name: 'Génie Civil', href: '#', icon: 'pi-cog' },
    { name: 'Contact', href: route('contact'), icon: 'pi-envelope' }
];

const footerLinks = [
    { title: "Solutions", links: ["Internet Satellite", "Starlink Business", "Fibre Optique", "Audit Réseau"] },
    { title: "Académie", links: ["Catalogue Cours", "Espace Étudiant", "Certifications", "Recrutement"] }
];

// Logique
const handleScroll = () => { scrolled.value = window.scrollY > 50; };
const goToLogin = () => { router.get('/login'); };

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
