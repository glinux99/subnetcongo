<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue Components
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Galleria from 'primevue/galleria';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ScrollPanel from 'primevue/scrollpanel';
import Tooltip from 'primevue/tooltip';

/**
 * --- ÉTATS & FILTRES ---
 */
const activeFilter = ref('Tous');
const selectedProject = ref(null);
const displayCustom = ref(false);

const categories = [
    'Tous',
    'VSAT & Satellites',
    'Réseaux Enterprise',
    'Fibre Optique',
    'Data Centers',
    'Starlink Business',
    'Cybersécurité'
];

/**
 * --- DATA : LES RÉALISATIONS (GALERIE) ---
 * 30+ Entrées prévues pour la richesse du contenu
 */
const realizations = ref([
    {
        id: 1,
        title: "Backbone VSAT - Site Minier Kolwezi",
        category: "VSAT & Satellites",
        client: "Glencore KCC",
        location: "Lualaba, RDC",
        date: "Mars 2024",
        image: "https://images.unsplash.com/photo-1516387153523-dec125583849?q=80&w=2070",
        description: "Installation d'une station terrienne redondante pour assurer la connectivité critique du site minier.",
        tags: ["C-Band", "iDirect", "Redondance"],
        featured: true
    },
    {
        id: 2,
        title: "Déploiement Starlink Business - 15 Sites",
        category: "Starlink Business",
        client: "Action Contre la Faim (ACF)",
        location: "Nord-Kivu / Ituri",
        date: "Janvier 2024",
        image: "https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?q=80&w=2072",
        description: "Interconnexion de bases humanitaires isolées via la constellation Starlink avec firewalling avancé.",
        tags: ["Starlink Business", "Fortinet", "Solaire"],
        featured: true
    },
    {
        id: 3,
        title: "Audit & Sécurisation LAN Gombe",
        category: "Cybersécurité",
        client: "Banque Centrale du Congo",
        location: "Kinshasa",
        date: "Novembre 2023",
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        description: "Refonte complète de l'architecture réseau et implémentation de sondes IDS/IPS.",
        tags: ["Audit", "Cisco", "ISO 27001"],
        featured: false
    },
    {
        id: 4,
        title: "Câblage Structuré & Salle Serveur",
        category: "Data Centers",
        client: "Rawbank HQ",
        location: "Kinshasa",
        date: "Août 2023",
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        description: "Aménagement d'une salle serveur climatisée avec extinction automatique et 500 points réseau.",
        tags: ["Nexans", "APC", "Schneider"],
        featured: true
    },
    {
        id: 5,
        title: "Boucle de Fibre Optique Urbaine",
        category: "Fibre Optique",
        client: "Gouvernorat du Haut-Katanga",
        location: "Lubumbashi",
        date: "Juin 2023",
        image: "https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070",
        description: "Déploiement de 12km de fibre optique ADSS pour l'interconnexion des ministères provinciaux.",
        tags: ["Soudure FO", "OTDR", "Cisco"],
        featured: false
    },
    {
        id: 6,
        title: "Réseau WiFi Haute Densité",
        category: "Réseaux Enterprise",
        client: "Hôtel du Fleuve",
        location: "Kinshasa",
        date: "Avril 2023",
        image: "https://images.unsplash.com/photo-1563986768609-322da13575f3?q=80&w=2070",
        description: "Couverture WiFi 6 totale pour les 300 chambres et espaces de conférence.",
        tags: ["Ubiquiti", "WiFi 6", "VLANs"],
        featured: false
    },
    {
        id: 7,
        title: "Station VSAT Ku-Band Humanitaire",
        category: "VSAT & Satellites",
        client: "PAM (Programme Alimentaire Mondial)",
        location: "Kalemie",
        date: "Février 2024",
        image: "https://images.unsplash.com/photo-1518186285589-2f7649de83e0?q=80&w=1974",
        description: "Lien satellite dédié pour la coordination des secours en zone de conflit.",
        tags: ["Ku-Band", "Humanitaire", "Reliability"],
        featured: false
    },
    {
        id: 8,
        title: "Sécurisation Cloud Hybride",
        category: "Cybersécurité",
        client: "Congo Telecom",
        location: "Kinshasa / Brazzaville",
        date: "Décembre 2023",
        image: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        description: "Mise en place de VPN IPsec multisites et protection anti-DDoS.",
        tags: ["VPN", "Firewall", "ISP"],
        featured: false
    },
    {
        id: 9,
        title: "Infrastructure Radio Micro-ondes",
        category: "Réseaux Enterprise",
        client: "Société Nationale des Chemins de Fer (SNCC)",
        location: "Lualaba",
        date: "Octobre 2023",
        image: "https://images.unsplash.com/photo-1520105072000-f44fc083e50b?q=80&w=2070",
        description: "Liaisons point-à-point longue distance pour le monitoring ferroviaire.",
        tags: ["MikroTik", "Point-to-Point", "Radio"],
        featured: false
    },
    {
        id: 10,
        title: "Système de Vidéosurveillance IP",
        category: "Cybersécurité",
        client: "Port de Matadi",
        location: "Kongo-Central",
        date: "Septembre 2023",
        image: "https://images.unsplash.com/photo-1557597774-9d2739f85a76?q=80&w=2071",
        description: "Déploiement de 120 caméras Hikvision avec stockage centralisé et analyse intelligente.",
        tags: ["CCTV", "VMS", "AI Detection"],
        featured: false
    }
]);

/**
 * --- DATA : ÉTUDES DE CAS DÉTAILLÉES ---
 */
const caseStudies = ref([
    {
        id: 1,
        title: "Optimisation de la Connectivité pour Glencore KCC",
        client: "Glencore KCC",
        industry: "Mines",
        challenge: "Le site minier souffrait de latences importantes et de coupures fréquentes sur son ancien lien satellite, impactant la production et la sécurité des travailleurs isolés.",
        solution: "Déploiement d'une solution hybride combinant un lien VSAT C-Band professionnel pour la voix/ERP et un cluster Starlink Business pour l'accès internet haut débit des employés. Gestion intelligente du trafic via SD-WAN.",
        results: "Disponibilité du réseau passée de 85% à 99.9%. Réduction de la latence de 600ms à 45ms pour les applications critiques. Satisfaction utilisateur en hausse de 200%.",
        stats: [
            { label: "Uptime", value: "99.9%" },
            { label: "Bande passante", value: "+400%" },
            { label: "ROI", value: "14 Mois" }
        ],
        techStack: ["Cisco SD-WAN", "Starlink High Performance", "Antenne VSAT 3.8m"],
        image: "https://images.unsplash.com/photo-1579389083395-4507e9f4c171?q=80&w=2070"
    },
    {
        id: 2,
        title: "Modernisation IT de la Rawbank",
        client: "Rawbank",
        industry: "Finance / Banque",
        challenge: "Nécessité de mettre à niveau le data center principal pour supporter la croissance exponentielle des transactions mobiles tout en respectant les normes de sécurité bancaire internationales.",
        solution: "Refonte du câblage structuré en Cat6A et Fibre OM4. Installation d'un système de confinement d'allée chaude (Hot Aisle Containment) et redondance électrique N+1.",
        results: "Capacité de traitement doublée. Consommation énergétique réduite de 30% grâce au refroidissement optimisé. Certification de conformité obtenue sans réserve.",
        stats: [
            { label: "Points Réseau", value: "1200+" },
            { label: "Énergie économisée", value: "30%" },
            { label: "Délai", value: "45 Jours" }
        ],
        techStack: ["APC Netshelter", "Nexans LANmark", "Schneider Electric Power"],
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070"
    },
    {
        id: 3,
        title: "Urgence Humanitaire : Connectivité Nord-Kivu",
        client: "Action Contre la Faim",
        industry: "ONG / Humanitaire",
        challenge: "Coordonner les secours dans des zones sans aucune infrastructure télécom stable, souvent sous tension sécuritaire.",
        solution: "Kits de connectivité nomades auto-déployables basés sur Starlink Business, protégés par des valises Pelican durcies avec batteries lithium et panneaux solaires.",
        results: "Communication rétablie en moins de 15 minutes sur chaque nouveau site. Suivi en temps réel des flottes de véhicules et sécurité accrue du personnel.",
        stats: [
            { label: "Temps de Setup", value: "15 min" },
            { label: "Sites déployés", value: "24" },
            { label: "Autonomie", value: "72h" }
        ],
        techStack: ["Starlink", "EcoFlow Power", "Pelican Cases"],
        image: "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=2070"
    }
]);

/**
 * --- DATA : LOGOS CLIENTS (MOCKUP) ---
 */
const clients = [
    { name: "Glencore", logo: "https://upload.wikimedia.org/wikipedia/en/thumb/0/0e/Glencore_logo.svg/1200px-Glencore_logo.svg.png", type: "Mines" },
    { name: "Rawbank", logo: "#", type: "Banque" },
    { name: "PAM", logo: "#", type: "Humanitaire" },
    { name: "Congo Telecom", logo: "#", type: "Télécoms" },
    { name: "Tenke Fungurume", logo: "#", type: "Mines" },
    { name: "UNICEF", logo: "#", type: "Humanitaire" },
    { name: "Vodacom RDC", logo: "#", type: "Télécoms" },
    { name: "Standard Bank", logo: "#", type: "Banque" }
];

/**
 * --- LOGIQUE DE FILTRAGE ---
 */
const filteredRealizations = computed(() => {
    if (activeFilter.value === 'Tous') return realizations.value;
    return realizations.value.filter(item => item.category === activeFilter.value);
});

const featuredProjects = computed(() => realizations.value.filter(p => p.featured));

/**
 * --- HELPERS ---
 */
const getCategoryColor = (cat) => {
    switch (cat) {
        case 'VSAT & Satellites': return 'bg-blue-600';
        case 'Starlink Business': return 'bg-red-600';
        case 'Cybersécurité': return 'bg-slate-900';
        case 'Data Centers': return 'bg-amber-600';
        default: return 'bg-[#003399]';
    }
};

</script>

<template>
    <Head title="Nos Réalisations - Subnet Academy Expertise" />

    <AppLayout>
        <!-- --- [SECTION 1] HERO RÉALISATIONS --- -->
        <header class="relative min-h-[85vh] flex items-center pt-32 pb-20 bg-slate-950 overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072" class="w-full h-full object-cover opacity-30" />
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent"></div>
                <!-- Grid Pattern overlay -->
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 40px 40px;"></div>
            </div>

            <div class="relative z-10 max-w-[1500px] mx-auto px-6 w-full">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                    <div class="space-y-12">
                        <div class="inline-flex items-center gap-4 px-6 py-2 bg-red-600/10 border border-red-600/20 rounded-full text-red-500">
                            <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.4em]">Déploiements Réels sur le Terrain</span>
                        </div>

                        <h1 class="text-6xl md:text-[7.5rem] font-black text-white leading-[0.85] tracking-tighter italic">
                            Bâtir l'Infra <br/>
                            <span class="text-[#003399] drop-shadow-[0_5px_15px_rgba(0,51,153,0.5)]">Numérique</span> <br/>
                            du Congo.
                        </h1>

                        <p class="text-2xl text-slate-400 font-medium leading-relaxed max-w-xl italic border-l-4 border-red-600 pl-8">
                            De la forêt équatoriale aux gratte-ciels de Kinshasa, nous connectons les organisations les plus exigeantes avec une précision chirurgicale.
                        </p>

                        <div class="grid grid-cols-3 gap-10 pt-10">
                            <div class="group cursor-help">
                                <p class="text-5xl font-black text-white italic group-hover:text-red-500 transition-colors">450+</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2">Sites VSAT</p>
                            </div>
                            <div class="group cursor-help">
                                <p class="text-5xl font-black text-white italic group-hover:text-red-500 transition-colors">120K</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2">Mètres de Fibre</p>
                            </div>
                            <div class="group cursor-help">
                                <p class="text-5xl font-black text-white italic group-hover:text-red-500 transition-colors">24/7</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2">Support Terrain</p>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Case Preview -->
                    <div class="hidden lg:block">
                        <div class="relative group">
                            <div class="absolute -inset-4 bg-gradient-to-r from-[#003399] to-red-600 rounded-3xl blur-2xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                            <div class="relative bg-slate-900 border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
                                <img :src="realizations[0].image" class="w-full h-80 object-cover grayscale hover:grayscale-0 transition-all duration-700" />
                                <div class="p-10 space-y-6">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="text-white text-2xl font-black italic">Projet Glencore KCC</h4>
                                            <p class="text-slate-400 text-sm">Station Terrienne Master & Réseau Mesh</p>
                                        </div>
                                        <Tag severity="danger" value="Étude de Cas" class="uppercase font-black px-4 py-1.5" />
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs font-bold text-white italic">C-Band Redundancy</div>
                                        <div class="px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs font-bold text-white italic">99.9% Uptime</div>
                                    </div>
                                    <button class="w-full py-5 bg-white text-slate-950 font-black rounded-2xl hover:bg-red-600 hover:text-white transition-all">Découvrir ce projet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- --- [SECTION 2] FILTRES & GALERIE --- -->
        <section class="py-32 bg-white px-6">
            <div class="max-w-[1500px] mx-auto">
                <div class="flex flex-col lg:flex-row justify-between items-end gap-12 mb-24">
                    <div class="space-y-6 max-w-2xl">
                        <h2 class="text-red-600 font-black uppercase tracking-[0.5em] text-sm italic">Portefeuille</h2>
                        <h3 class="text-6xl font-black text-slate-950 tracking-tighter leading-none italic">Nos <span class="text-[#003399]">Réalisations</span> de Terrain.</h3>
                        <p class="text-xl text-slate-500 font-medium italic">Explorez nos derniers déploiements à travers la République Démocratique du Congo.</p>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <button v-for="cat in categories" :key="cat"
                                @click="activeFilter = cat"
                                :class="['px-8 py-4 rounded-full font-black text-xs uppercase tracking-widest transition-all', activeFilter === cat ? 'bg-slate-950 text-white shadow-2xl' : 'bg-slate-50 text-slate-400 hover:bg-slate-100']">
                            {{ cat }}
                        </button>
                    </div>
                </div>

                <!-- Projects Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <transition-group name="project-list">
                        <div v-for="project in filteredRealizations" :key="project.id"
                             class="group relative h-[600px] rounded-[2.5rem] overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-700 bg-slate-50">

                            <img :src="project.image" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>

                            <div class="absolute top-8 left-8 flex flex-col gap-3">
                                <div :class="['px-6 py-2 rounded-full text-white text-[10px] font-black uppercase tracking-widest shadow-xl', getCategoryColor(project.category)]">
                                    {{ project.category }}
                                </div>
                                <div v-if="project.featured" class="px-6 py-2 bg-white text-slate-950 rounded-full text-[10px] font-black uppercase tracking-widest shadow-xl">
                                    ⭐ Top Réalisation
                                </div>
                            </div>

                            <div class="absolute bottom-10 left-10 right-10 transform translate-y-8 group-hover:translate-y-0 transition-transform duration-500">
                                <p class="text-red-500 font-black text-xs uppercase tracking-widest mb-3">{{ project.client }} • {{ project.location }}</p>
                                <h4 class="text-3xl font-black text-white italic mb-6 leading-tight">{{ project.title }}</h4>

                                <div class="space-y-6 opacity-0 group-hover:opacity-100 transition-opacity delay-100">
                                    <p class="text-slate-300 font-medium italic text-sm line-clamp-2">{{ project.description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="tag in project.tags" :key="tag" class="px-4 py-1.5 bg-white/10 backdrop-blur-xl rounded-lg text-[9px] font-bold text-white uppercase">{{ tag }}</span>
                                    </div>
                                    <button class="w-full py-4 bg-red-600 text-white font-black rounded-xl hover:bg-[#003399] transition-all flex items-center justify-center gap-3">
                                        Détails Techniques <i class="pi pi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition-group>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 3] CLIENTS SHOWCASE --- -->
        <section class="py-32 bg-slate-50 border-y border-slate-100">
            <div class="max-w-[1500px] mx-auto px-6">
                <div class="text-center mb-24 space-y-4">
                    <h2 class="text-[#003399] font-black uppercase tracking-[0.5em] text-sm italic">Confiance</h2>
                    <h3 class="text-5xl font-black text-slate-950 tracking-tighter italic">Ils nous font <span class="text-red-600">Confiance</span>.</h3>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-10 items-center opacity-60">
                    <div v-for="i in 12" :key="i" class="h-32 bg-white rounded-3xl border border-slate-100 flex items-center justify-center p-8 hover:opacity-100 hover:shadow-xl transition-all cursor-pointer group">
                        <!-- Simulation de logos clients -->
                        <span class="text-slate-300 font-black italic group-hover:text-slate-950 transition-colors">CLIENT_LOGO_{{ i }}</span>
                    </div>
                </div>

                <div class="mt-20 flex flex-wrap justify-center gap-20">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center text-red-600">
                            <i class="pi pi-briefcase text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-950 italic">Secteur Minier</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Connectivité Critique</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center text-[#003399]">
                            <i class="pi pi-heart text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-950 italic">Humanitaire (ONG)</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Aide & Secours</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center text-amber-600">
                            <i class="pi pi-building text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-950 italic">Institutions</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Gouvernement & Banques</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 4] ÉTUDES DE CAS (CAS D'ÉCOLE) --- -->
        <section class="py-40 px-6 bg-white">
            <div class="max-w-[1500px] mx-auto">
                <div class="flex items-center gap-8 mb-32">
                    <div class="h-px flex-1 bg-slate-100"></div>
                    <h3 class="text-5xl font-black text-slate-950 tracking-tighter italic px-8">Études de <span class="text-red-600 underline decoration-slate-200 underline-offset-8">Cas Détaillées</span>.</h3>
                    <div class="h-px flex-1 bg-slate-100"></div>
                </div>

                <div class="space-y-40">
                    <div v-for="(study, index) in caseStudies" :key="study.id"
                         :class="['flex flex-col lg:flex-row gap-20 items-center', index % 2 !== 0 ? 'lg:flex-row-reverse' : '']">

                        <!-- Visual -->
                        <div class="w-full lg:w-1/2 relative">
                            <div :class="['absolute -inset-6 rounded-[3.5rem] opacity-20 blur-3xl', index % 2 === 0 ? 'bg-[#003399]' : 'bg-red-600']"></div>
                            <div class="relative rounded-[3rem] overflow-hidden shadow-2xl border border-slate-100 aspect-video">
                                <img :src="study.image" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-slate-900/40 mix-blend-multiply"></div>
                                <div class="absolute bottom-10 left-10 right-10 p-8 bg-white/10 backdrop-blur-2xl rounded-2xl border border-white/20">
                                    <div class="grid grid-cols-3 gap-8">
                                        <div v-for="stat in study.stats" :key="stat.label">
                                            <p class="text-3xl font-black text-white italic leading-none">{{ stat.value }}</p>
                                            <p class="text-[9px] font-black uppercase text-white/60 tracking-widest mt-2">{{ stat.label }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="w-full lg:w-1/2 space-y-10">
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 text-red-600 font-black uppercase text-xs tracking-widest italic">
                                    <span>{{ study.industry }}</span>
                                    <span class="w-1.5 h-1.5 bg-slate-200 rounded-full"></span>
                                    <span>{{ study.client }}</span>
                                </div>
                                <h4 class="text-5xl font-black text-slate-950 leading-tight italic tracking-tighter">{{ study.title }}</h4>
                            </div>

                            <div class="space-y-8">
                                <div class="flex gap-8 group">
                                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-900 font-black group-hover:bg-red-600 group-hover:text-white transition-all shadow-sm">
                                        01
                                    </div>
                                    <div class="space-y-3">
                                        <h5 class="text-xl font-black text-slate-950 italic">Le Problème</h5>
                                        <p class="text-slate-500 font-medium italic leading-relaxed">{{ study.challenge }}</p>
                                    </div>
                                </div>

                                <div class="flex gap-8 group">
                                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-900 font-black group-hover:bg-[#003399] group-hover:text-white transition-all shadow-sm">
                                        02
                                    </div>
                                    <div class="space-y-3">
                                        <h5 class="text-xl font-black text-slate-950 italic">La Solution Déployée</h5>
                                        <p class="text-slate-500 font-medium italic leading-relaxed">{{ study.solution }}</p>
                                    </div>
                                </div>

                                <div class="flex gap-8 group">
                                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-900 font-black group-hover:bg-green-600 group-hover:text-white transition-all shadow-sm">
                                        03
                                    </div>
                                    <div class="space-y-3">
                                        <h5 class="text-xl font-black text-slate-950 italic">Résultats & Impact</h5>
                                        <p class="text-slate-500 font-medium italic leading-relaxed">{{ study.results }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-10 flex flex-wrap gap-4">
                                <Tag v-for="tech in study.techStack" :key="tech" :value="tech" severity="secondary" class="bg-slate-100 text-slate-950 font-black px-6 py-2 rounded-xl text-[10px] uppercase border-none" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- [SECTION 5] PROCESSUS & EXPERTISE TECHNIQUE --- -->
        <section class="py-32 bg-slate-950 text-white relative overflow-hidden">
            <div class="max-w-[1500px] mx-auto px-6 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-20 items-start">
                    <div class="lg:col-span-5 space-y-12">
                        <div class="space-y-6">
                            <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm">Méthodologie</h2>
                            <h3 class="text-6xl font-black tracking-tighter italic leading-[0.9]">Comment nous <br/> <span class="text-blue-500">Garantissons</span> <br/> le Succès.</h3>
                        </div>
                        <p class="text-xl text-slate-400 font-medium italic leading-relaxed">Pas d'improvisation. Chaque déploiement suit un protocole strict certifié par les standards internationaux et adapté aux contraintes de terrain de la RDC.</p>

                        <div class="space-y-4">
                            <div class="p-8 bg-white/5 border border-white/10 rounded-3xl hover:bg-white/10 transition-all cursor-pointer">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-2xl font-black italic">Site Survey & Audit</h4>
                                    <i class="pi pi-compass text-red-500 text-2xl"></i>
                                </div>
                                <p class="text-slate-500 text-sm italic font-medium">Analyse spectrale, étude de sol et topographie pour garantir une ligne de vue (LOS) parfaite.</p>
                            </div>
                            <div class="p-8 bg-white/5 border border-white/10 rounded-3xl hover:bg-white/10 transition-all cursor-pointer">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-2xl font-black italic">Installation & Alignement</h4>
                                    <i class="pi pi-cog text-blue-500 text-2xl"></i>
                                </div>
                                <p class="text-slate-500 text-sm italic font-medium">Pointage haute précision et soudure de fibre par fusion pour une atténuation minimale.</p>
                            </div>
                            <div class="p-8 bg-white/5 border border-white/10 rounded-3xl hover:bg-white/10 transition-all cursor-pointer">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-2xl font-black italic">Monitoring 24/7</h4>
                                    <i class="pi pi-chart-line text-green-500 text-2xl"></i>
                                </div>
                                <p class="text-slate-500 text-sm italic font-medium">Gestion proactive via notre NOC centralisé à Kinshasa pour une maintenance préventive.</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7 bg-white/5 border border-white/10 rounded-[3.5rem] p-12 lg:p-20 relative overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-16">
                            <div class="space-y-8">
                                <h5 class="text-2xl font-black italic border-b border-white/10 pb-6 uppercase tracking-widest text-red-500">Moyens Matériels</h5>
                                <ul class="space-y-6">
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-check-circle text-green-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Flotte de Véhicules 4x4</p>
                                            <p class="text-xs text-slate-500 font-medium">Équipés pour les interventions en zones reculées.</p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-check-circle text-green-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Soudeuses Fibre Fujikura</p>
                                            <p class="text-xs text-slate-500 font-medium">Dernière génération pour des pertes quasi-nulles.</p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-check-circle text-green-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Testeurs de réseaux Fluke</p>
                                            <p class="text-xs text-slate-500 font-medium">Certification Cat6, Cat6A et Fibre Optique.</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-8">
                                <h5 class="text-2xl font-black italic border-b border-white/10 pb-6 uppercase tracking-widest text-blue-500">Expertise Humaine</h5>
                                <ul class="space-y-6">
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-users text-blue-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Ingénieurs Certifiés CCIE</p>
                                            <p class="text-xs text-slate-500 font-medium">Experts en routage et switching avancé.</p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-shield text-blue-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Spécialistes GVF</p>
                                            <p class="text-xs text-slate-500 font-medium">Experts certifiés pour les installations satellites.</p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-4">
                                        <i class="pi pi-bolt text-blue-500 mt-1"></i>
                                        <div>
                                            <p class="font-black italic">Électriciens Industriel</p>
                                            <p class="text-xs text-slate-500 font-medium">Installation d'onduleurs et systèmes solaires.</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Technical Illustration Background -->
                        <div class="absolute -bottom-20 -right-20 opacity-5 pointer-events-none">
                            <i class="pi pi-cog text-[30rem] animate-spin-slow"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Background Decors -->
            <div class="absolute top-0 left-0 w-full h-1/2 bg-gradient-to-b from-[#003399]/10 to-transparent"></div>
        </section>

        <!-- --- [SECTION 6] FAQ RÉALISATIONS --- -->
        <section class="py-32 px-6 bg-white">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-24 space-y-4">
                    <h2 class="text-red-600 font-black uppercase tracking-[0.5em] text-sm italic">Infos Pratiques</h2>
                    <h3 class="text-6xl font-black text-slate-950 tracking-tighter italic">Questions sur nos <span class="text-[#003399]">Déploiements</span>.</h3>
                </div>

                <Accordion :multiple="true" class="subnet-accordion">
                    <AccordionTab header="Intervenez-vous partout en RDC ?">
                        <p class="text-slate-500 italic font-medium leading-relaxed">
                            Oui, nous avons une capacité de déploiement nationale. Grâce à nos bases logistiques à Kinshasa, Lubumbashi et Goma, nous couvrons les 26 provinces, y compris les zones les plus difficiles d'accès par voie terrestre ou fluviale.
                        </p>
                    </AccordionTab>
                    <AccordionTab header="Quels sont vos délais de déploiement pour Starlink Business ?">
                        <p class="text-slate-500 italic font-medium leading-relaxed">
                            Pour les kits en stock, nous pouvons déployer sous 48h à 72h à Kinshasa et Lubumbashi. Pour les déploiements multisites en province, nous prévoyons généralement un calendrier de 7 à 14 jours incluant la logistique.
                        </p>
                    </AccordionTab>
                    <AccordionTab header="Proposez-vous une garantie sur vos installations ?">
                        <p class="text-slate-500 italic font-medium leading-relaxed">
                            Absolument. Toutes nos installations sont garanties 1 an (pièces et main d'œuvre). Nous proposons également des contrats de maintenance préventive (SLA) avec des temps de rétablissement garantis (GTR) allant jusqu'à 4h pour les sites critiques.
                        </p>
                    </AccordionTab>
                    <AccordionTab header="Peut-on visiter l'une de vos réalisations ?">
                        <p class="text-slate-500 italic font-medium leading-relaxed">
                            Sur autorisation préalable de nos clients et pour des projets d'envergure similaire, nous pouvons organiser des visites de sites (notamment nos salles serveurs ou stations VSAT) pour que vous puissiez constater la qualité de notre câblage et de nos finitions.
                        </p>
                    </AccordionTab>
                </Accordion>
            </div>
        </section>

        <!-- --- [SECTION 7] CALL TO ACTION FINAL --- -->
        <section class="py-40 px-6 bg-[#003399] relative overflow-hidden">
            <div class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=2084" class="w-full h-full object-cover opacity-10" />
                <div class="absolute inset-0 bg-gradient-to-br from-[#003399] via-[#003399]/90 to-red-600/40"></div>
            </div>

            <div class="relative z-10 max-w-[1500px] mx-auto text-center space-y-16">
                <h3 class="text-5xl md:text-[7rem] font-black text-white leading-[0.85] tracking-tighter italic">
                    Confiez votre Infrastructure <br/>
                    <span class="text-slate-950 underline decoration-red-600 underline-offset-[20px]">aux Meilleurs du Terrain</span>.
                </h3>

                <p class="text-2xl text-blue-100/70 font-medium italic max-w-3xl mx-auto leading-relaxed">
                    Vous avez un projet complexe ? Un site isolé à connecter ? Une salle serveur à moderniser ? Nos ingénieurs sont prêts pour le briefing.
                </p>

                <div class="flex flex-wrap justify-center gap-8">
                    <button class="px-16 py-8 bg-white text-slate-950 rounded-3xl font-black text-xl hover:bg-red-600 hover:text-white transition-all shadow-2xl flex items-center gap-6 group">
                        Demander un Audit Gratuit <i class="pi pi-external-link group-hover:rotate-45 transition-transform"></i>
                    </button>
                    <button class="px-16 py-8 bg-transparent border-4 border-white/20 text-white rounded-3xl font-black text-xl hover:bg-white/10 transition-all shadow-xl backdrop-blur-xl">
                        Télécharger notre Brochure (PDF)
                    </button>
                </div>

                <!-- Footer Stats -->
                <div class="pt-20 grid grid-cols-2 md:grid-cols-4 gap-12 border-t border-white/10">
                    <div v-for="i in 4" :key="i">
                        <p class="text-4xl font-black text-white italic">100%</p>
                        <p class="text-[10px] font-bold text-blue-200 uppercase tracking-[0.3em] mt-2">Satisfaction Client</p>
                    </div>
                </div>
            </div>
        </section>

    </AppLayout>
</template>

<style scoped>
/* --- ANIMATIONS DE LISTE --- */
.project-list-enter-active,
.project-list-leave-active {
  transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
.project-list-enter-from,
.project-list-leave-to {
  opacity: 0;
  transform: translateY(50px) scale(0.9);
}

/* --- ACCORDION CUSTOM --- */
:deep(.subnet-accordion .p-accordion-header-link) {
    background: transparent !important;
    border: none !important;
    padding: 2.5rem 0 !important;
    border-bottom: 1px solid #f1f5f9 !important;
    box-shadow: none !important;
    flex-direction: row-reverse !important;
    justify-content: space-between !important;
}

:deep(.subnet-accordion .p-accordion-header-text) {
    font-size: 1.75rem !important;
    font-weight: 900 !important;
    font-style: italic !important;
    color: #0f172a !important;
}

:deep(.subnet-accordion .p-accordion-content) {
    background: transparent !important;
    border: none !important;
    padding: 2rem 0 3rem 0 !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

:deep(.p-accordion-toggle-icon) {
    color: #EE1C25 !important;
    font-size: 1.5rem !important;
}

/* --- ANIMATION SPIN SLOW --- */
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}

/* --- TYPOGRAPHIE --- */
.tracking-tighter {
    letter-spacing: -0.06em;
}

/* Custom Selection Color */
::selection {
    background: #EE1C25;
    color: white;
}

/* Scrollbar Style */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #0f172a; }
::-webkit-scrollbar-thumb { background: #EE1C25; border-radius: 10px; }

/* Image Hover Zoom effect fixed height */
.group:hover img {
    transform: scale(1.08);
}
</style>
