<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

/**
 * --- DATA : HISTOIRE DÉTAILLÉE (TIMELINE) ---
 */
const history = [
  {
    year: "2014",
    title: "La Genèse à Goma",
    desc: "Fondation de SubnetCongo avec l'ambition de briser l'isolement numérique du Nord-Kivu. Premier déploiement de boucles locales radio.",
    icon: "pi-flag",
    image: "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=2070",
    tags: ["Fondation", "Goma", "WISP"]
  },
  {
    year: "2016",
    title: "Incursion Minière",
    desc: "Signature de contrats majeurs pour la connectivité VSAT en bande Ku pour les sites miniers isolés du Katanga et de l'Ituri.",
    icon: "pi-map",
    image: "https://images.unsplash.com/photo-1579389083046-e3df9c2b3325?q=80&w=2070",
    tags: ["Secteur Minier", "VSAT", "B2B"]
  },
  {
    year: "2018",
    title: "Hub Fibre Optique",
    desc: "Interconnexion avec les backbones sous-marins via l'Afrique de l'Est. Lancement des premiers services Fiber-to-the-Business (FTTB).",
    icon: "pi-share-alt",
    image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
    tags: ["Fibre Optique", "Haut Débit"]
  },
  {
    year: "2020",
    title: "Naissance de l'Académie",
    desc: "Ouverture du premier centre de formation certifié Cisco à Goma. Objectif : autonomiser la jeunesse congolaise dans la Tech.",
    icon: "pi-graduation-cap",
    image: "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070",
    tags: ["Éducation", "Cisco", "Impact"]
  },
  {
    year: "2022",
    title: "Expansion Nationale",
    desc: "Ouverture de bureaux de représentation à Kinshasa et Lubumbashi. Subnet devient un acteur national incontournable.",
    icon: "pi-external-link",
    image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070",
    tags: ["National", "Infrastructure"]
  },
  {
    year: "2024",
    title: "Révolution Starlink",
    desc: "Certification officielle comme revendeur et installateur Starlink Business en RDC. Connectivité satellite LEO à basse latence.",
    icon: "pi-bolt",
    image: "https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?q=80&w=2072",
    tags: ["Starlink", "LEO Satellite", "Innovation"]
  },
  {
    year: "2026",
    title: "Cap 2030",
    desc: "Lancement de notre propre Data Center Green à Goma pour l'hébergement de données souveraines congolaises.",
    icon: "pi-database",
    image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
    tags: ["Cloud Souverain", "Green Tech"]
  }
];

/**
 * --- DATA : VALEURS STRATÉGIQUES ---
 */
const coreValues = [
  {
    title: "Souveraineté Numérique",
    icon: "pi-shield",
    desc: "Nous militons pour que les données congolaises restent sur le sol congolais, garantissant sécurité et indépendance nationale."
  },
  {
    title: "Innovation de Rupture",
    icon: "pi-lightbulb",
    desc: "Toujours à l'avant-garde, nous avons été les premiers à introduire des technologies comme le SD-WAN et le Starlink LEO en RDC."
  },
  {
    title: "Impact Communautaire",
    icon: "pi-users",
    desc: "Subnet réinvestit massivement dans la formation. Nous ne vendons pas seulement du net, nous créons des experts."
  },
  {
    title: "Résilience",
    icon: "pi-sync",
    desc: "Opérant dans des environnements complexes, notre infrastructure est conçue pour rester debout malgré les défis géopolitiques."
  }
];

/**
 * --- DATA : TECH SPECS ---
 */
const techSpecs = [
  { label: "Uptime Annuel", value: "99.98%", icon: "pi-clock", detail: "Architecture redondante via 3 passerelles internationales différentes." },
  { label: "Latence Moyenne", value: "< 25ms", icon: "pi-fast-forward", detail: "Optimisation de routage vers les serveurs européens et africains." },
  { label: "Sites Actifs", value: "1,450+", icon: "pi-server", detail: "Plus grand réseau de stations VSAT privées déployé en zone rurale." },
  { label: "Talents Certifiés", value: "15,000+", icon: "pi-briefcase", detail: "Nombre d'étudiants ayant validé un cursus à la Subnet Academy." },
  { label: "Capacité Backbone", value: "200 Gbps", icon: "pi-database", detail: "Bande passante totale disponible via fibre et satellite combinés." },
  { label: "Support NOC", value: "24/7/365", icon: "pi-headphones", detail: "Équipe d'ingénieurs en astreinte permanente à Goma et Kinshasa." }
];

/**
 * --- DATA : LEADERSHIP ---
 */
const team = [
  {
    name: "Eng. Patient M.",
    role: "Directeur Général & Fondateur",
    bio: "Visionnaire de l'infrastructure numérique en Afrique Centrale, il a piloté plus de 500 projets de télécommunications critiques.",
    photo: "https://i.pravatar.cc/400?u=dg",
    linkedIn: "#"
  },
  {
    name: "Sarah Kahindo",
    role: "Directrice Technique (CTO)",
    bio: "Spécialiste en ingénierie radio et fibre optique. Elle supervise le déploiement du réseau national de Subnet.",
    photo: "https://i.pravatar.cc/400?u=cto",
    linkedIn: "#"
  },
  {
    name: "Marc Luvambo",
    role: "Directeur Académique",
    bio: "Expert certifié Cisco CCIE. Il est l'architecte derrière les programmes de formation d'élite de Subnet Academy.",
    photo: "https://i.pravatar.cc/400?u=acad",
    linkedIn: "#"
  },
  {
    name: "Amani Bisimwa",
    role: "Directeur Expérience Client",
    bio: "Ancien consultant en stratégie, il assure que la qualité de service dépasse les standards internationaux.",
    photo: "https://i.pravatar.cc/400?u=cust",
    linkedIn: "#"
  }
];

/**
 * --- DATA : CSR (RSE) ---
 */
const csrProjects = [
  {
    title: "Connect Schools",
    location: "Beni, Butembo & Walikale",
    desc: "Fourniture gratuite d'Internet haut débit et de salles informatiques à 50 écoles primaires en zones sinistrées.",
    image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=2022",
    metric: "12,000 élèves connectés"
  },
  {
    title: "Health-Link RDC",
    location: "Zones de Santé du Nord-Kivu",
    desc: "Connectivité satellite pour les hôpitaux ruraux permettant la télémédecine et le suivi épidémiologique en temps réel.",
    image: "https://images.unsplash.com/photo-1584515933487-779824d29309?q=80&w=2070",
    metric: "45 centres de santé"
  },
  {
    title: "Femmes dans la Tech",
    location: "National",
    desc: "Bourses d'études complètes pour les jeunes femmes souhaitant obtenir des certifications Cisco et Microsoft.",
    image: "https://images.unsplash.com/photo-1573164060821-4b41a6a76c5b?q=80&w=2070",
    metric: "200 bourses annuelles"
  }
];

/**
 * --- DATA : CERTIFICATIONS ---
 */
const certs = [
  { name: "Cisco Authorized Learning Partner", logo: "https://placehold.co/150x80/white/003399?text=CISCO" },
  { name: "Starlink Business Authorized", logo: "https://placehold.co/150x80/white/003399?text=STARLINK" },
  { name: "ISO 9001:2015 Quality MGMT", logo: "https://placehold.co/150x80/white/003399?text=ISO+9001" },
  { name: "ARPTC Licensed Operator", logo: "https://placehold.co/150x80/white/003399?text=ARPTC" },
  { name: "MikroTik Certified Training", logo: "https://placehold.co/150x80/white/003399?text=MIKROTIK" },
  { name: "Fortinet Advantage Partner", logo: "https://placehold.co/150x80/white/003399?text=FORTINET" }
];
</script>

<template>
  <Head title="À Propos - L'Excellence Technologique au Congo" />

  <AppLayout>
    <!-- --- [SECTION 1] HERO CINÉMATIQUE --- -->
    <header class="relative min-h-[100vh] flex items-center pt-32 pb-20 bg-[#001A4D] overflow-hidden">
      <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-black/60 z-10"></div>
        <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072" class="w-full h-full object-cover scale-110 animate-slow-zoom" />
        <div class="absolute inset-0 bg-gradient-to-r from-[#001A4D] via-[#001A4D]/70 to-transparent z-20"></div>
      </div>

      <div class="relative z-30 max-w-[1500px] mx-auto px-6 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-9 space-y-10">
            <div class="inline-flex items-center gap-4 px-8 py-3 bg-white/10 backdrop-blur-3xl border border-white/20 rounded-full text-white shadow-2xl animate-fade-in">
              <span class="flex h-3 w-3 rounded-full bg-red-600 animate-ping"></span>
              <span class="text-xs font-black uppercase tracking-[0.5em]">L'Expertise du Réseau Panafricain</span>
            </div>

            <h1 class="text-6xl md:text-[8.5rem] font-black text-white leading-[0.85] tracking-tighter">
              Bâtir l'Autoroute <br/>
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-orange-500 to-yellow-400 italic">
                Numérique de la RDC.
              </span>
            </h1>

            <p class="text-2xl md:text-3xl text-blue-100/80 max-w-4xl font-medium leading-relaxed border-l-8 border-red-600 pl-10">
              Plus qu'un fournisseur, SubnetCongo est l'architecte de la connectivité souveraine, alliant infrastructure de pointe et éducation d'élite.
            </p>

            <div class="flex flex-wrap gap-4 pt-10">
              <div v-for="t in ['ISP', 'ACADEMY', 'GÉNIE CIVIL', 'CLOUD']" :key="t" class="px-6 py-3 bg-white/5 border border-white/10 rounded-xl text-white font-black text-[10px] tracking-[0.3em]">
                {{ t }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Overlay Ornemental -->
      <div class="absolute bottom-0 right-0 w-1/3 h-full bg-gradient-to-l from-red-600/10 to-transparent pointer-events-none"></div>
    </header>

    <!-- --- [SECTION 2] LE MANIFESTE : BENTO GRID --- -->
    <section class="py-32 px-6 bg-white">
      <div class="max-w-[1500px] mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Main Info -->
          <div class="lg:col-span-8 bg-slate-900 rounded-[3rem] p-16 text-white flex flex-col justify-center relative overflow-hidden group shadow-2xl">
            <div class="relative z-10">
              <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm mb-8">Notre Manifeste</h2>
              <h3 class="text-5xl md:text-7xl font-black leading-tight tracking-tighter mb-10 italic">"Pas de progrès sans <span class="text-blue-400 underline decoration-red-600 underline-offset-8">maîtrise technique</span> locale."</h3>
              <p class="text-2xl text-slate-300 font-medium leading-relaxed max-w-3xl">
                Fondée en 2014 au cœur de Goma, SubnetCongo SARL est née d'un constat simple : la connectivité est le moteur de la paix et du développement. Nous avons construit nos réseaux là où les autres jugeaient l'accès impossible.
              </p>
            </div>
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-red-600/10 rounded-full blur-3xl group-hover:bg-red-600/20 transition-all duration-700"></div>
          </div>

          <!-- Side Stats -->
          <div class="lg:col-span-4 grid grid-cols-1 gap-8">
             <div class="bg-blue-600 rounded-[3rem] p-12 text-white shadow-xl flex flex-col justify-between">
                <i class="pi pi-heart-fill text-4xl mb-6"></i>
                <p class="text-4xl font-black italic mb-2">12 Ans</p>
                <p class="text-blue-100 font-bold uppercase tracking-widest text-xs">De service ininterrompu en RDC.</p>
             </div>
             <div class="bg-slate-50 rounded-[3rem] p-12 text-slate-900 shadow-xl border border-slate-100">
                <p class="text-sm font-black uppercase tracking-widest text-slate-400 mb-6 italic">Basés à Goma</p>
                <h4 class="text-3xl font-black leading-tight italic">Fièrement établi au pied des Volcans.</h4>
             </div>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 3] L'ODYSSÉE : TIMELINE VERTICALE --- -->
    <section class="py-32 bg-slate-50 relative overflow-hidden">
      <div class="max-w-[1500px] mx-auto px-6">
        <div class="text-center mb-32 space-y-6">
          <h2 class="text-[#003399] font-black uppercase tracking-[0.5em] text-sm">L'Odyssée</h2>
          <h3 class="text-6xl md:text-8xl font-black text-slate-900 tracking-tighter italic">Une Décennie d'Impact.</h3>
          <p class="text-xl text-slate-500 max-w-2xl mx-auto font-medium">De petite entreprise locale à leader national de l'infrastructure numérique.</p>
        </div>

        <div class="relative">
          <!-- Line Decor -->
          <div class="absolute left-1/2 top-0 w-1 h-full bg-slate-200 -translate-x-1/2 hidden lg:block"></div>

          <div class="space-y-32">
            <div v-for="(event, idx) in history" :key="event.year"
                 class="relative flex flex-col lg:flex-row items-center gap-16 group">

              <!-- Text Side -->
              <div :class="['w-full lg:w-1/2 flex flex-col', idx % 2 === 0 ? 'lg:items-end lg:text-right' : 'lg:order-last lg:items-start lg:text-left']">
                <div class="space-y-6 max-w-xl">
                  <span class="text-[12rem] font-black text-slate-200/50 leading-none block italic group-hover:text-red-500/10 transition-colors duration-700 select-none">{{ event.year }}</span>
                  <div class="flex flex-wrap gap-2 mb-4" :class="idx % 2 === 0 ? 'lg:justify-end' : 'lg:justify-start'">
                    <span v-for="tag in event.tags" :key="tag" class="px-4 py-1 bg-white rounded-full text-[9px] font-black uppercase text-slate-500 shadow-sm">{{ tag }}</span>
                  </div>
                  <h4 class="text-4xl font-black text-slate-900">{{ event.title }}</h4>
                  <p class="text-xl text-slate-500 leading-relaxed font-medium italic">{{ event.desc }}</p>
                </div>
              </div>

              <!-- Node Decor -->
              <div class="absolute left-1/2 -translate-x-1/2 w-20 h-20 bg-white rounded-full border-[6px] border-[#003399] z-10 hidden lg:flex items-center justify-center shadow-2xl group-hover:scale-125 transition-transform duration-500">
                <i :class="['pi text-2xl text-red-600', event.icon]"></i>
              </div>

              <!-- Image Side -->
              <div class="w-full lg:w-1/2">
                <div class="relative h-[500px] rounded-[3.5rem] overflow-hidden shadow-2xl group-hover:rotate-1 transition-transform duration-700">
                  <img :src="event.image" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-1000" />
                  <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 4] TECH SPECS : L'INFRASTRUCTURE --- -->
    <section class="py-32 bg-[#001A4D] text-white relative overflow-hidden">
      <div class="max-w-[1500px] mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-center">
          <div class="space-y-12">
            <div>
              <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm mb-6">Prouesse Technique</h2>
              <h3 class="text-6xl md:text-[5.5rem] font-black leading-[0.9] tracking-tighter italic">Une Infrastructure de <br/> <span class="text-blue-400 underline decoration-red-600 underline-offset-8">Classe Mondiale</span>.</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div v-for="tech in techSpecs" :key="tech.label" class="p-10 bg-white/5 rounded-[2.5rem] border border-white/10 hover:bg-white/10 transition-all group">
                <div class="flex items-center gap-6 mb-6">
                  <div class="w-14 h-14 bg-red-600 rounded-2xl flex items-center justify-center text-white shadow-xl group-hover:rotate-12 transition-transform">
                    <i :class="['pi text-2xl', tech.icon]"></i>
                  </div>
                  <span class="text-4xl font-black italic">{{ tech.value }}</span>
                </div>
                <p class="text-blue-300 font-black uppercase tracking-widest text-[10px] mb-4 opacity-70">{{ tech.label }}</p>
                <p class="text-sm text-slate-400 leading-relaxed font-medium">{{ tech.detail }}</p>
              </div>
            </div>
          </div>

          <div class="relative">
             <div class="relative z-10 bg-gradient-to-br from-blue-900 to-black p-4 rounded-[4rem] shadow-2xl border border-white/10 animate-float">
                <img src="https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070" class="rounded-[3.5rem] h-[700px] w-full object-cover" />
                <div class="absolute -bottom-10 -left-10 bg-white p-10 rounded-3xl shadow-2xl text-slate-900 max-w-xs border border-slate-100">
                  <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white">
                      <i class="pi pi-shield text-xl"></i>
                    </div>
                    <span class="font-black italic">Monitoring NOC</span>
                  </div>
                  <p class="text-sm font-medium text-slate-500">Supervision en temps réel de tous les backbones fibre et satellite.</p>
                </div>
             </div>
             <!-- Decor Blur -->
             <div class="absolute -top-20 -right-20 w-96 h-96 bg-red-600/20 rounded-full blur-[150px]"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 5] LES VALEURS : LES 4 PILIERS --- -->
    <section class="py-32 bg-white px-6">
      <div class="max-w-[1500px] mx-auto text-center mb-24">
         <h2 class="text-red-600 font-black uppercase tracking-[0.5em] text-sm">ADN Subnet</h2>
         <h3 class="text-6xl font-black text-slate-900 tracking-tighter italic">L'Éthique au service de la Tech.</h3>
      </div>
      <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        <div v-for="val in coreValues" :key="val.title" class="p-12 rounded-[3.5rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl transition-all group">
          <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mb-10 shadow-xl group-hover:bg-[#003399] transition-all">
            <i :class="['pi text-3xl text-red-600 group-hover:text-white', val.icon]"></i>
          </div>
          <h4 class="text-2xl font-black text-slate-900 mb-6 italic">{{ val.title }}</h4>
          <p class="text-slate-500 font-medium leading-relaxed italic">{{ val.desc }}</p>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 6] IMPACT SOCIAL (CSR/RSE) --- -->
    <section class="py-32 px-6 bg-slate-900 relative overflow-hidden">
      <div class="max-w-[1500px] mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row justify-between items-end mb-24 gap-10">
          <div class="max-w-3xl">
            <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm mb-6 italic">Impact Social (RSE)</h2>
            <h3 class="text-5xl md:text-7xl font-black text-white leading-tight tracking-tighter">Plus que des octets, <br/> une <span class="italic text-blue-400">Responsabilité</span>.</h3>
          </div>
          <p class="text-xl text-slate-400 font-medium max-w-sm italic">Subnet réinvestit 10% de sa capacité technique pour soutenir l'éducation et la santé en zones rurales.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div v-for="project in csrProjects" :key="project.title" class="group bg-white/5 rounded-[3rem] overflow-hidden border border-white/10 hover:border-red-500 transition-all duration-500">
            <div class="h-80 relative overflow-hidden">
              <img :src="project.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" />
              <div class="absolute top-6 left-6 px-6 py-2 bg-red-600 text-white font-black text-[10px] rounded-full uppercase tracking-widest shadow-xl">
                {{ project.metric }}
              </div>
            </div>
            <div class="p-12 space-y-4">
              <h4 class="text-2xl font-black text-white italic">{{ project.title }}</h4>
              <p class="text-slate-500 font-bold text-xs uppercase tracking-[0.3em]">{{ project.location }}</p>
              <p class="text-slate-400 leading-relaxed font-medium">{{ project.desc }}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="absolute -bottom-20 -left-20 w-[500px] h-[500px] bg-red-600/5 rounded-full blur-[150px]"></div>
    </section>

    <!-- --- [SECTION 7] LEADERSHIP : LE CAPITAL HUMAIN --- -->
    <section class="py-32 px-6 bg-white">
      <div class="max-w-[1500px] mx-auto">
        <div class="text-center mb-24 space-y-4">
          <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm">Leadership</h2>
          <h3 class="text-6xl font-black text-slate-900 tracking-tighter italic">Les Visages derrière l'Innovation.</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
          <div v-for="member in team" :key="member.name" class="group">
            <div class="relative overflow-hidden rounded-[3rem] mb-10 shadow-2xl h-[500px]">
              <img :src="member.photo" class="w-full h-full object-cover grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-[#003399]/95 via-[#003399]/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500 flex flex-col justify-end p-12">
                <a :href="member.linkedIn" target="_blank" class="w-14 h-14 bg-white text-[#003399] rounded-2xl flex items-center justify-center text-xl hover:bg-red-600 hover:text-white transition-colors shadow-2xl">
                  <i class="pi pi-linkedin"></i>
                </a>
              </div>
            </div>
            <h4 class="text-3xl font-black text-slate-900 mb-1 italic">{{ member.name }}</h4>
            <p class="text-[#EE1C25] font-black uppercase tracking-widest text-[10px] mb-6 italic">{{ member.role }}</p>
            <p class="text-slate-500 text-sm leading-relaxed font-medium italic border-l-2 border-slate-100 pl-4">{{ member.bio }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 8] PARTENAIRES & CERTIFICATIONS --- -->
    <section class="py-32 border-y border-slate-100 bg-white overflow-hidden">
      <div class="max-w-[1500px] mx-auto px-6 text-center">
        <p class="text-[10px] font-black uppercase tracking-[0.5em] text-slate-400 mb-20 italic">Un Écosystème Global de Confiance</p>
        <div class="flex flex-wrap justify-center items-center gap-12 md:gap-24 opacity-40 hover:opacity-100 transition-opacity grayscale hover:grayscale-0 duration-700">
          <div v-for="c in certs" :key="c.name" class="flex flex-col items-center gap-4 group cursor-pointer">
             <img :src="c.logo" class="h-12 w-auto object-contain group-hover:scale-110 transition-transform" />
             <p class="text-[8px] font-black text-slate-400 group-hover:text-red-600 transition-colors uppercase tracking-widest">{{ c.name }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 9] ACADEMY FOCUS --- -->
    <section class="py-32 bg-slate-50">
      <div class="max-w-[1500px] mx-auto px-6">
        <div class="bg-white rounded-[4rem] p-12 md:p-24 shadow-2xl border border-slate-100 relative">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-center">
            <div class="space-y-12">
              <div class="inline-flex items-center gap-4 text-[#003399]">
                <div class="w-12 h-1 bg-[#003399]"></div>
                <span class="font-black uppercase tracking-widest text-sm italic">Pépinière de Talents</span>
              </div>
              <h3 class="text-5xl md:text-7xl font-black text-slate-900 leading-tight tracking-tighter italic">Transmettre pour <br/> <span class="text-red-600">Pérenniser</span>.</h3>
              <p class="text-2xl text-slate-500 font-medium leading-relaxed italic border-l-4 border-red-500 pl-8">
                Nous ne nous contentons pas de former des techniciens ; nous forgeons des ingénieurs capables de maintenir les réseaux critiques de demain.
              </p>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div v-for="point in academyPoints" :key="point" class="flex items-center gap-6 p-6 bg-slate-50 rounded-2xl group hover:bg-[#003399] transition-all duration-500">
                  <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[#EE1C25] shadow-sm group-hover:scale-110 transition-transform">
                    <i class="pi pi-check text-xs"></i>
                  </div>
                  <span class="text-slate-700 font-black uppercase tracking-tight text-xs group-hover:text-white">{{ point }}</span>
                </div>
              </div>
            </div>

            <div class="relative grid grid-cols-2 gap-6">
                <div class="space-y-6">
                  <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=2070" class="rounded-[2.5rem] h-64 w-full object-cover shadow-2xl" />
                  <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070" class="rounded-[2.5rem] h-80 w-full object-cover shadow-2xl mt-12" />
                </div>
                <div class="space-y-6 pt-12">
                  <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?q=80&w=2070" class="rounded-[2.5rem] h-80 w-full object-cover shadow-2xl" />
                  <div class="bg-red-600 rounded-[2.5rem] p-10 flex flex-col justify-center text-white shadow-2xl h-64">
                    <p class="text-5xl font-black italic mb-2">92%</p>
                    <p class="text-xs font-black uppercase tracking-widest leading-tight">De taux de réussite aux examens internationaux.</p>
                  </div>
                </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 10] CALL TO ACTION FINAL --- -->
    <section class="py-32 px-6 bg-white overflow-hidden relative">
      <div class="max-w-[1500px] mx-auto text-center relative z-10">
        <div class="mb-16">
          <i class="pi pi-globe text-7xl text-[#003399] animate-spin-slow-once opacity-10 inline-block mb-10"></i>
          <h3 class="text-5xl md:text-[5.5rem] font-black text-slate-900 leading-[0.9] tracking-tighter italic">Ensemble, bâtissons la <br/> <span class="text-red-600">Souveraineté de demain</span>.</h3>
        </div>
        <div class="flex flex-wrap justify-center gap-8">
           <button class="px-16 py-8 bg-slate-900 text-white rounded-3xl font-black text-xl hover:bg-[#003399] transition-all shadow-2xl flex items-center gap-6 group">
             Demander une étude gratuite <i class="pi pi-arrow-right group-hover:translate-x-4 transition-transform"></i>
           </button>
           <button class="px-16 py-8 bg-white border-4 border-slate-900 text-slate-900 rounded-3xl font-black text-xl hover:bg-slate-900 hover:text-white transition-all shadow-xl">
             Nous contacter
           </button>
        </div>
      </div>
      <!-- Ornements de fond -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-red-600/5 rounded-full blur-[150px]"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-600/5 rounded-full blur-[150px]"></div>
    </section>

  </AppLayout>
</template>

<style scoped>
/* --- ANIMATIONS SPÉCIFIQUES --- */
@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0); }
  50% { transform: translateY(-30px) rotate(2deg); }
}

@keyframes slow-zoom {
  0% { transform: scale(1); }
  100% { transform: scale(1.15); }
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes spin-slow-once {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-float {
  animation: float 8s ease-in-out infinite;
}

.animate-slow-zoom {
  animation: slow-zoom 25s linear infinite alternate;
}

.animate-fade-in {
  animation: fade-in 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-spin-slow-once {
    animation: spin-slow-once 40s linear infinite;
}

/* --- GLASSMORPHISM --- */
.backdrop-blur-3xl {
  backdrop-filter: blur(64px);
  -webkit-backdrop-filter: blur(64px);
}

/* --- SCROLLBAR STYLE --- */
::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #f8fafc;
}
::-webkit-scrollbar-thumb {
    background: #003399;
    border-radius: 20px;
}
</style>
