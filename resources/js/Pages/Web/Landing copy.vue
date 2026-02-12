<template>
  <div class="min-h-screen bg-[#FDFFFF] text-slate-900 font-sans selection:bg-red-100 selection:text-red-600 overflow-x-hidden">

    <!-- --- [1] NAVIGATION AVANCÉE --- -->
    <nav :class="['fixed w-full z-[100] transition-all duration-700 px-6 py-3', scrolled ? 'bg-white/90 backdrop-blur-2xl shadow-2xl border-b border-slate-100 py-2' : 'bg-transparent']">
      <div class="max-w-[1400px] mx-auto flex justify-between items-center">
        <!-- Logo -->
        <div class="flex items-center gap-3 group cursor-pointer">
          <div class="relative overflow-hidden rounded-xl transition-transform duration-500 group-hover:scale-110">
            <img src="https://subnetcongo.net/assets/img/logo.png" alt="SubnetCongo" class="h-12 w-auto object-contain" />
          </div>
          <div class="hidden lg:block">
            <span class="text-xl font-black tracking-tighter text-[#003399]">SUBNET<span class="text-[#EE1C25]">CONGO</span></span>
            <p class="text-[8px] font-bold uppercase tracking-[0.3em] text-slate-500 -mt-1">Connecter & Éduquer</p>
          </div>
        </div>

        <!-- Menu Desktop Centré -->
        <div class="hidden lg:flex items-center gap-1 bg-slate-100/50 p-1.5 rounded-2xl border border-slate-200/50">
          <template v-for="item in navLinks" :key="item.name">
            <a :href="item.href" class="px-5 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-[#003399] hover:bg-white transition-all duration-300">
              {{ item.name }}
            </a>
          </template>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button class="hidden sm:flex items-center gap-2 px-4 py-2 text-sm font-black text-[#003399] hover:bg-blue-50 rounded-xl transition-all">
            <i class="pi pi-user-plus"></i> Espace Client
          </button>
          <button class="relative group overflow-hidden bg-[#EE1C25] text-white px-8 py-3 rounded-xl font-black shadow-lg shadow-red-200 transition-all hover:scale-105 active:scale-95">
            <span class="relative z-10 flex items-center gap-2">Rejoindre l'Académie <i class="pi pi-arrow-up-right text-xs"></i></span>
            <div class="absolute inset-0 bg-gradient-to-r from-blue-800 to-blue-600 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
          </button>
        </div>
      </div>
    </nav>

    <!-- --- [2] HERO SECTION HYBRIDE (ANTENNE + EDUCATION) --- -->
    <section class="relative min-h-[110vh] flex items-center pt-20 overflow-hidden bg-[#001A4D]">
      <!-- Background Elements -->
      <div class="absolute inset-0 z-0">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-600/10 to-transparent"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-600/20 rounded-full blur-[120px]"></div>
        <!-- Vidéo ou Image Loop en fond -->
        <img src="https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070"
             class="w-full h-full object-cover opacity-20 mix-blend-overlay" />
      </div>

      <div class="relative z-10 max-w-[1400px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
        <!-- Texte Hero -->
        <div class="lg:col-span-7 space-y-8">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white">
            <span class="flex h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
            <span class="text-xs font-black uppercase tracking-widest">Technologie & Savoir sans frontières</span>
          </div>

          <h1 class="text-5xl md:text-8xl font-black text-white leading-[1.05] tracking-tighter">
            Connectez-vous <br/>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-400">À l'Avenir du Congo.</span>
          </h1>

          <p class="text-xl text-blue-100/70 max-w-2xl font-medium leading-relaxed">
            Nous ne nous contentons pas d'installer des antennes. Nous construisons des ponts entre votre potentiel et le monde grâce à une connexion haut débit et des formations d'élite.
          </p>

          <div class="flex flex-wrap gap-4">
            <button class="bg-white text-blue-900 px-10 py-5 rounded-2xl font-black text-lg shadow-2xl hover:bg-red-500 hover:text-white transition-all transform hover:-translate-y-2 flex items-center gap-3">
              Découvrir nos Solutions ISP <i class="pi pi-wifi"></i>
            </button>
            <div class="flex items-center gap-4 px-6 py-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
              <div class="flex -space-x-4">
                <div v-for="i in 4" :key="i" class="w-10 h-10 rounded-full border-2 border-blue-900 bg-slate-300"></div>
              </div>
              <div>
                <p class="text-white font-bold text-sm">+15k Apprenants</p>
                <div class="flex text-amber-400 text-[10px]"><i class="pi pi-star-fill" v-for="i in 5" :key="i"></i></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Visuel Montage Antenne / Education -->
        <div class="lg:col-span-5 relative">
          <div class="relative z-20 rounded-[3rem] overflow-hidden border-[12px] border-white/5 shadow-2xl animate-float">
            <img src="https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069" class="w-full h-[600px] object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-blue-900 via-transparent to-transparent"></div>

            <!-- Floating Card 1: Antenne -->
            <div class="absolute bottom-10 left-6 right-6 bg-white/10 backdrop-blur-2xl p-6 rounded-3xl border border-white/20 translate-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="pi pi-map-marker text-xl"></i>
                    </div>
                    <div>
                        <p class="text-white font-black">Installation VSAT & Fibre</p>
                        <p class="text-blue-200 text-xs">Partout en RDC, même en zone rurale.</p>
                    </div>
                </div>
            </div>
          </div>
          <!-- Cercle Décoratif -->
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-600 rounded-full mix-blend-overlay filter blur-3xl opacity-50 animate-pulse"></div>
        </div>
      </div>

      <!-- Curve Separator -->
      <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-[0]">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="relative block w-full h-[100px] fill-white">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C58.05,110.19,132.31,109.58,192,108c66.21-1.74,135-15.44,129.39-51.56Z"></path>
        </svg>
      </div>
    </section>

    <!-- --- [3] SECTION ISP & INFRASTRUCTURE --- -->
    <section class="py-24 px-6 relative bg-white">
      <div class="max-w-[1400px] mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-20 space-y-4">
            <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm">Le Réseau du Futur</h2>
            <h3 class="text-5xl font-black text-slate-900 tracking-tight leading-tight">Nous connectons là où les autres <span class="text-[#003399]">s'arrêtent</span>.</h3>
            <p class="text-slate-500 font-medium">Expertise technique en installation d'antennes paraboliques, configuration Starlink, fibre optique et maintenance réseau.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div v-for="service in ispServices" :key="service.title"
                 class="group p-10 bg-slate-50 rounded-[3rem] border border-transparent hover:border-red-100 hover:bg-white hover:shadow-2xl transition-all duration-500">
                <div :class="`w-20 h-20 rounded-[2rem] flex items-center justify-center mb-8 bg-white shadow-xl group-hover:scale-110 transition-transform duration-500` ">
                    <i :class="`pi ${service.icon} text-3xl text-${service.color}-600` "></i>
                </div>
                <h4 class="text-2xl font-black text-slate-900 mb-4">{{ service.title }}</h4>
                <p class="text-slate-500 leading-relaxed mb-8 font-medium">{{ service.desc }}</p>
                <ul class="space-y-3 mb-10">
                    <li v-for="feat in service.features" :key="feat" class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="pi pi-check-circle text-[#EE1C25]"></i> {{ feat }}
                    </li>
                </ul>
                <button class="w-full py-4 rounded-2xl bg-slate-900 text-white font-black group-hover:bg-[#003399] transition-colors">
                    Demander un devis
                </button>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [4] BENTO GRID E-LEARNING --- -->
    <section class="py-24 px-6 bg-[#001A4D] overflow-hidden relative">
      <!-- Animated background mesh -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:40px_40px]"></div>
      </div>

      <div class="max-w-[1400px] mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row justify-between items-end mb-20 gap-8">
            <div class="max-w-2xl">
                <h2 class="text-red-500 font-black uppercase tracking-[0.4em] text-sm mb-4">Subnet Academy</h2>
                <h3 class="text-4xl md:text-6xl font-black text-white tracking-tighter leading-tight">Apprenez avec des <br/> <span class="italic text-blue-400 underline decoration-red-500 underline-offset-8">Leaders de l'industrie</span>.</h3>
            </div>
            <button class="px-10 py-5 bg-white rounded-2xl font-black text-blue-900 hover:bg-red-500 hover:text-white transition-all">
                Voir tous les cours (450+)
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Featured Course Card -->
            <div class="md:col-span-8 bg-gradient-to-br from-blue-800 to-blue-950 rounded-[3rem] p-10 relative overflow-hidden group border border-white/10">
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <span class="bg-red-600 text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase w-fit tracking-widest">Nouveau & Exclusif</span>
                    <div class="mt-20">
                        <h4 class="text-4xl font-black text-white mb-6 max-w-md">Ingénierie Réseaux & Installation Télécom Expert</h4>
                        <p class="text-blue-200 text-lg mb-8 max-w-sm">Devenez un technicien certifié capable de déployer des infrastructures partout en Afrique.</p>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-clock text-red-500"></i>
                                <span class="text-white font-bold text-sm">120 Heures</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-briefcase text-red-500"></i>
                                <span class="text-white font-bold text-sm">Niveau Pro</span>
                            </div>
                        </div>
                    </div>
                </div>
                <img src="https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070" class="absolute top-0 right-0 w-1/2 h-full object-cover opacity-20 group-hover:scale-110 transition-transform duration-1000" />
            </div>

            <!-- Side Course Cards -->
            <div class="md:col-span-4 space-y-6">
                <div v-for="c in courses" :key="c.id" class="bg-white/5 backdrop-blur-md rounded-[2.5rem] p-8 border border-white/10 hover:bg-white/10 transition-all cursor-pointer group">
                    <div class="flex gap-6">
                        <div class="w-16 h-16 rounded-2xl bg-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-red-500 group-hover:text-white transition-all">
                            <i :class="`pi ${c.icon} text-2xl` "></i>
                        </div>
                        <div>
                            <h5 class="text-white font-black mb-2 text-lg line-clamp-1">{{ c.title }}</h5>
                            <p class="text-blue-200/50 text-sm font-bold uppercase tracking-widest">{{ c.category }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-[#EE1C25] rounded-[2.5rem] p-8 text-white flex items-center justify-between group cursor-pointer hover:scale-105 transition-all">
                    <span class="text-xl font-black">Besoin d'aide ?</span>
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-red-600">
                        <i class="pi pi-whatsapp text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [5] SECTION PHILOSOPHIE & RDC --- -->
    <section class="py-32 px-6">
        <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
            <div class="relative">
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-red-100 rounded-full blur-3xl opacity-50"></div>
                <div class="grid grid-cols-2 gap-4">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070" class="rounded-[2rem] shadow-2xl h-80 w-full object-cover mt-12" />
                    <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=2070" class="rounded-[2rem] shadow-2xl h-80 w-full object-cover" />
                </div>
                <!-- Badge flottant -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#003399] p-10 rounded-full border-[10px] border-white text-white text-center shadow-2xl">
                    <p class="text-4xl font-black italic tracking-tighter">Depuis 10 ans</p>
                    <p class="text-xs font-bold uppercase tracking-[0.2em]">Au service du Congo</p>
                </div>
            </div>

            <div class="space-y-8">
                <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm italic">Notre Engagement</h2>
                <h3 class="text-5xl font-black text-slate-900 leading-tight">Pas de digital sans infrastructure, pas d'infrastructure sans <span class="text-[#003399]">maîtrise</span>.</h3>
                <p class="text-xl text-slate-500 leading-relaxed font-medium">
                    SubnetCongo SARL est une entreprise panafricaine basée à Kinshasa qui combine les services de fournisseur d'accès internet (FAI) et un centre de formation technologique.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="point in ['Installation Antennes (VSAT/Starlink)', 'Déploiement Fibre Optique', 'E-Learning Certifiant', 'Support Technique 24/7']"
                         :key="point" class="flex items-center gap-4 bg-slate-50 p-5 rounded-2xl border border-slate-100 group hover:bg-white hover:border-red-200 transition-all">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center text-[#EE1C25] group-hover:bg-[#EE1C25] group-hover:text-white transition-all">
                            <i class="pi pi-check text-xs"></i>
                        </div>
                        <span class="text-sm font-black text-slate-700">{{ point }}</span>
                    </div>
                </div>

                <div class="pt-6">
                    <button class="bg-slate-900 text-white px-12 py-5 rounded-2xl font-black text-lg hover:bg-[#003399] transition-all flex items-center gap-4 group">
                        Parler à un Expert <i class="pi pi-phone animate-bounce"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [6] FOOTER TERMINAL --- -->
    <footer class="bg-slate-900 pt-32 pb-12 px-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-red-600/10 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-600/10 rounded-full blur-[100px]"></div>

        <div class="max-w-[1400px] mx-auto relative z-10">
            <!-- Top Footer : Newsletter -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center pb-24 border-b border-white/5">
                <h4 class="text-4xl md:text-5xl font-black text-white tracking-tighter">Restez connecté aux <br/> opportunités du numérique.</h4>
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="email" placeholder="votre@email.com" class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-red-500 outline-none transition-all font-bold" />
                    <button class="bg-[#EE1C25] text-white px-10 py-5 rounded-2xl font-black hover:bg-white hover:text-red-600 transition-all">S'abonner</button>
                </div>
            </div>

            <!-- Mid Footer : Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 py-24">
                <div class="space-y-8">
                    <img src="https://subnetcongo.net/assets/img/logo.png" class="h-16 w-auto brightness-200" />
                    <p class="text-slate-400 font-medium leading-relaxed">
                        Siège Social : Avenue Colonel Mondjiba, Concession UtexAfrica, Kinshasa, RDC.
                    </p>
                    <div class="flex gap-4">
                        <a v-for="social in ['facebook', 'twitter', 'linkedin', 'instagram']" :key="social" href="#" class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-white hover:bg-[#003399] transition-all">
                            <i :class="`pi pi-${social}` "></i>
                        </a>
                    </div>
                </div>

                <div v-for="section in footerLinks" :key="section.title">
                    <h5 class="text-white font-black uppercase text-xs tracking-[0.3em] mb-10">{{ section.title }}</h5>
                    <ul class="space-y-4">
                        <li v-for="link in section.links" :key="link">
                            <a href="#" class="text-slate-400 font-bold hover:text-red-500 transition-all">{{ link }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-white font-black uppercase text-xs tracking-[0.3em] mb-10">Certification & Qualité</h5>
                    <div class="bg-white/5 p-6 rounded-[2rem] border border-white/10 space-y-4">
                        <div class="flex items-center gap-4 text-white">
                            <i class="pi pi-verified text-2xl text-red-500"></i>
                            <span class="font-black">Partenaire Agréé Starlink</span>
                        </div>
                        <div class="flex items-center gap-4 text-white">
                            <i class="pi pi-verified text-2xl text-red-500"></i>
                            <span class="font-black">Licence FAI (ARPTC)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:row justify-between items-center gap-6 pt-12 border-t border-white/5">
                <p class="text-slate-500 font-bold text-sm">© 2024 SUBNETCONGO SARL. Tous droits réservés.</p>
                <div class="flex items-center gap-8">
                    <a href="#" class="text-xs font-black text-slate-500 hover:text-white uppercase tracking-widest">Confidentialité</a>
                    <a href="#" class="text-xs font-black text-slate-500 hover:text-white uppercase tracking-widest">Mentions Légales</a>
                    <a href="#" class="text-xs font-black text-slate-500 hover:text-white uppercase tracking-widest">CGV</a>
                </div>
            </div>
        </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

// --- LOGIQUE NAVBAR ---
const scrolled = ref(false);
const handleScroll = () => {
    scrolled.value = window.scrollY > 50;
};

// --- DONNÉES DYNAMIQUES ---
const navLinks = [
    { name: 'Solutions Internet', href: '#' },
    { name: 'Subnet Academy', href: '#' },
    { name: 'Génie Civil & Infra', href: '#' },
    { name: 'Partenariats', href: '#' },
    { name: 'Support', href: '#' }
];

const ispServices = [
    {
        title: "Installation Antennes VSAT",
        icon: "pi-globe",
        color: "blue",
        desc: "Connexion satellitaire haute performance pour les zones minières et forestières reculées de la RDC.",
        features: ["Bande Ku & Ka", "Latence optimisée", "Support site physique"]
    },
    {
        title: "Starlink for Business",
        icon: "pi-bolt",
        color: "red",
        desc: "Revendeur et installateur agréé Starlink en RDC pour une connexion internet ultra-rapide et stable.",
        features: ["Vitesse jusqu'à 300 Mbps", "Kit Auto-orientable", "IP Statique possible"]
    },
    {
        title: "Fibre Optique Dédiée",
        icon: "pi-sitemap",
        color: "blue",
        desc: "Solutions de fibre noire et FTTH pour les entreprises exigeantes et les sièges sociaux à Kinshasa/Goma.",
        features: ["Uptime 99.9%", "Bande passante symétrique", "GTR 4 Heures"]
    }
];

const courses = [
    { id: 1, title: "Administration Réseaux Cisco CCNA", category: "Réseaux", icon: "pi-database" },
    { id: 2, title: "Cybersécurité & Audit SI", category: "Sécurité", icon: "pi-lock" },
    { id: 3, title: "DevOps & Cloud Computing", category: "Cloud", icon: "pi-cloud" },
    { id: 4, title: "Intelligence Artificielle & Big Data", category: "Data", icon: "pi-cog" }
];

const footerLinks = [
    {
        title: "Nos Solutions",
        links: ["Internet par Satellite", "Liaisons Louées", "Audit de Réseau", "Hosting & Datacenter"]
    },
    {
        title: "E-Learning",
        links: ["Catalogue des cours", "Cours Gratuits", "Certification Pro", "Espace Tuteur"]
    }
];

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');

body {
    font-family: 'Inter', sans-serif;
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
    100% { transform: translateY(0px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Scrollbar Customization */
::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #003399;
    border-radius: 5px;
}
::-webkit-scrollbar-thumb:hover {
    background: #EE1C25;
}

/* Glassmorphism helpers */
.bg-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
