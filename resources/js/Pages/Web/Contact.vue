<script setup>
import { ref, reactive, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Import PrimeVue Components
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import 'primeicons/primeicons.css';
import { parsePhoneNumberFromString } from 'libphonenumber-js';
/**
 * --- GESTION DU FORMULAIRE ---
 */
const form = useForm({
    name: '',
    email: '',
    country: { name: 'Congo (DRC)', code: 'CD', dial_code: '+243' },
    phone: '',
    subject: null, // Changé à null pour le Dropdown
    message: '',
    privacy: false
});

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
watch([() => form.phone, () => form.country], ([newPhone, newCountry]) => {
    if (newPhone && newCountry) {
        // Nettoyage du numéro (on enlève le 0 au début si présent)
        const cleanedPhone = newPhone.startsWith('0') ? newPhone.substring(1) : newPhone;
        const fullNumber = newCountry.dial_code + cleanedPhone;

        try {
            const phoneNumber = parsePhoneNumberFromString(fullNumber, newCountry.code);
            if (phoneNumber && phoneNumber.isValid()) {
                form.clearErrors('phone');
            } else {
                form.setError('phone', 'Format invalide pour ce pays.');
            }
        } catch (e) {
            form.setError('phone', 'Erreur de validation.');
        }
    } else {
        form.clearErrors('phone');
    }
});
/**
 * --- DATA : DÉPARTEMENTS ---
 * Ajout de couleurs spécifiques pour un design plus riche
 */
const departments = [
    { name: 'Service Commercial & Ventes', code: 'sales', icon: 'pi-shopping-cart', color: 'text-blue-600', bg: 'bg-blue-50', desc: 'Devis Internet, Starlink & Solutions Entreprises.' },
    { name: 'Support Technique & NOC', code: 'tech', icon: 'pi-cog', color: 'text-red-600', bg: 'bg-red-50', desc: 'Assistance technique 24/7 et maintenance.', live: true },
    { name: 'Subnet Academy', code: 'academy', icon: 'pi-graduation-cap', color: 'text-amber-600', bg: 'bg-amber-50', desc: 'Inscriptions et informations sur les certifications.' },
    { name: 'Partenariats & Projets', code: 'partners', icon: 'pi-briefcase', color: 'text-indigo-600', bg: 'bg-indigo-50', desc: 'Opportunités de collaboration et projets civils.' }
];

const countries = ref([
    { name: 'Congo (DRC)', code: 'CD', dial_code: '+243' },
    { name: 'France', code: 'FR', dial_code: '+33' },
    { name: 'USA', code: 'US', dial_code: '+1' },
    { name: 'Congo', code: 'CG', dial_code: '+242' },
    { name: 'Rwanda', code: 'RW', dial_code: '+250' },
    { name: 'Belgium', code: 'BE', dial_code: '+32' },
]);

const getFlagUrl = (code) => `https://flagcdn.com/w40/${code.toLowerCase()}.png`;

/**
 * --- DATA : BUREAUX ---
 */
const offices = [
  {
    city: "Goma (Siège Social)",
    address: "N° 0001, Avenue Des Touristes, Quartier les Volcans",
    phone: "+243 970 912 428",
    email: "goma@subnetcongo.net",
    image: "https://images.unsplash.com/photo-1596463059283-da257325bee8?q=80&w=2070"
  },
  {
    city: "Kinshasa",
    address: "Bld du 30 Juin, Immeuble Royal, Gombe",
    phone: "+243 810 000 000",
    email: "kin@subnetcongo.net",
    image: "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=2070"
  },
  {
    city: "Bukavu",
    address: "Av. Mobutu, Quartier Nyalukemba, Ibanda",
    phone: "+243 850 000 000",
    email: "bukavu@subnetcongo.net",
    image: "https://images.unsplash.com/photo-1531973576160-7125cd663d86?q=80&w=2070"
  }
];

const quickFaqs = [
    { q: "Comment obtenir un devis Starlink ?", a: "Sélectionnez 'Service Commercial' et précisez votre localisation pour une étude de signal gratuite." },
    { q: "Support disponible le week-end ?", a: "Oui, notre équipe NOC assure une surveillance 24h/24, même les jours fériés." }
];
</script>

<template>
  <Head title="Contact - SubnetCongo" />

  <AppLayout>
    <!-- --- [SECTION 1] HERO --- -->
    <header class="relative min-h-[60vh] flex items-center pt-32 pb-20 bg-[#001A4D] overflow-hidden">
      <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-black/60 z-10"></div>
        <img src="https://images.unsplash.com/photo-1534536281715-e28d76689b4d?q=80&w=2070" class="w-full h-full object-cover scale-110 animate-slow-zoom" />
        <div class="absolute inset-0 bg-gradient-to-r from-[#001A4D] via-[#001A4D]/80 to-transparent z-20"></div>
      </div>

      <div class="relative z-30 max-w-[1500px] mx-auto px-6 w-full text-center lg:text-left">
          <div class="inline-flex items-center gap-4 px-6 py-2 bg-white/10 backdrop-blur-3xl border border-white/20 rounded-full text-white mb-8">
            <span class="flex h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.4em]">Support réactif 24h/24</span>
          </div>
          <h1 class="text-5xl md:text-8xl font-black text-white leading-tight tracking-tighter">
            L'Expertise à portée <br/>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-yellow-400 italic">de clic.</span>
          </h1>
      </div>
    </header>

    <!-- --- [SECTION 2] CONTACT GRID --- -->
    <section class="py-32 px-6 bg-white">
      <div class="max-w-[1500px] mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-20">

          <!-- COLONNE GAUCHE : DÉPARTEMENTS (L'expertise à portée de clic) -->
          <div class="lg:col-span-5 space-y-12">
            <div class="space-y-4">
                <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm">Nous sommes là</h2>
                <h3 class="text-5xl font-black text-slate-900 leading-[0.9] tracking-tighter">Choisissez votre <span class="text-[#003399]">expert</span>.</h3>
            </div>

            <!-- Bento List de Départements -->
            <div class="grid grid-cols-1 gap-4">
                <div v-for="dept in departments" :key="dept.name"
                     class="group p-6 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-6 hover:bg-white hover:shadow-lg hover:scale-[1.02] transition-all duration-500 cursor-pointer">

                    <div :class="['w-16 h-16 rounded-lg flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform', dept.bg]">
                        <i :class="['pi text-lg', dept.icon, dept.color]"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="text-lg font-black text-slate-900">{{ dept.name }}</h4>
                            <span v-if="dept.live" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-green-100 text-[8px] font-black text-green-600 uppercase animate-pulse">
                                <i class="pi pi-circle-fill text-[6px]"></i> Live
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 font-medium leading-relaxed italic">{{ dept.desc }}</p>
                    </div>

                    <i class="pi pi-chevron-right text-slate-300 group-hover:text-[#EE1C25] group-hover:translate-x-2 transition-all"></i>
                </div>
            </div>

            <!-- Urgent Card -->
            <div class="bg-gradient-to-br from-[#003399] to-blue-900 p-10 rounded-lg text-white relative overflow-hidden shadow-lg group">
                <div class="relative z-10 space-y-6">
                    <h4 class="text-3xl font-black italic">Canal WhatsApp</h4>
                    <p class="text-blue-100/70 font-medium leading-relaxed">Besoin d'une réponse instantanée ? Discutez en direct avec nos ingénieurs.</p>
                    <a href="https://wa.me/243970912428" target="_blank" class="inline-flex items-center gap-4 bg-[#25D366] px-8 py-4 rounded-lg font-black text-white hover:scale-105 transition-all shadow-lg">
                        <i class="pi pi-whatsapp text-lg"></i> +243 970 912 428
                    </a>
                </div>
                <i class="pi pi-whatsapp absolute -right-10 -bottom-10 text-[15rem] opacity-5 group-hover:rotate-12 transition-transform duration-700"></i>
            </div>
          </div>

          <!-- COLONNE DROITE : FORMULAIRE -->
          <div class="lg:col-span-7 bg-[#FDFFFF] rounded-lg p-8 md:p-16 shadow-[0_40px_100px_rgba(0,0,0,0.08)] border border-slate-50">
             <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-4">Nom Complet</label>
                        <InputText v-model="form.name" placeholder="Daniel KIKIMBA" class="subnet-input" required />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-4">Email Professionnel</label>
                        <InputText v-model="form.email" type="email" placeholder="genesiskikimba@gmail.com" class="subnet-input" required />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-4">Département visé</label>
                        <Dropdown v-model="form.subject" :options="departments" optionLabel="name"
                                  placeholder="Sélectionnez un expert" class="subnet-dropdown">
                            <template #option="slotProps">
                                <div class="flex items-center gap-3">
                                    <i :class="['pi text-[#EE1C25]', slotProps.option.icon]" />
                                    <span class="font-bold text-sm">{{ slotProps.option.name }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-4">Votre Message</label>
                    <textarea v-model="form.message" rows="5" placeholder="Décrivez votre besoin..." class="subnet-input resize-none pt-4"></textarea>
                </div>

                <div class="flex items-center gap-4 px-4 bg-slate-50 py-4 rounded-lg">
                    <input v-model="form.privacy" type="checkbox" id="privacy" class="w-5 h-5 rounded text-red-600 focus:ring-red-500 border-slate-200" required />
                    <label for="privacy" class="text-xs font-bold text-slate-500 italic">J'accepte la politique de confidentialité de SubnetCongo.</label>
                </div>

                <button type="submit" :disabled="form.processing" class="w-full py-8 bg-slate-900 text-white rounded-lg font-black text-xl hover:bg-[#EE1C25] transition-all shadow-lg flex items-center justify-center gap-6 group">
                   {{ form.processing ? 'Envoi...' : 'Propulser mon message' }}
                   <i class="pi pi-send group-hover:translate-x-4 group-hover:-translate-y-2 transition-transform"></i>
                </button>
             </form>
          </div>
        </div>
      </div>
    </section>

        <!-- --- [SECTION 3] NOS BUREAUX (CARDS) --- -->
    <section class="py-32 bg-slate-50 px-6">
        <div class="max-w-[1500px] mx-auto">
            <div class="text-center mb-24 space-y-4">
                <h2 class="text-[#003399] font-black uppercase tracking-[0.4em] text-sm">Présence Nationale</h2>
                <h3 class="text-6xl font-black text-slate-900 tracking-tighter italic">Où nous <span class="text-red-600">trouver</span> ?</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div v-for="office in offices" :key="office.city" class="group bg-white rounded-lg overflow-hidden shadow-xl border border-slate-100 hover:shadow-lg transition-all duration-700">
                    <div class="h-64 relative overflow-hidden">
                        <img :src="office.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                        <div class="absolute bottom-6 left-10 text-white">
                            <h4 class="text-3xl font-black italic">{{ office.city }}</h4>
                        </div>
                    </div>
                    <div class="p-10 space-y-8">
                        <div class="space-y-6">
                            <div class="flex items-start gap-6">
                                <i class="pi pi-map-marker text-red-600 mt-1"></i>
                                <p class="text-slate-500 font-medium leading-relaxed italic text-sm">{{ office.address }}</p>
                            </div>
                            <div class="flex items-center gap-6">
                                <i class="pi pi-phone text-[#003399]"></i>
                                <p class="text-slate-900 font-black text-sm">{{ office.phone }}</p>
                            </div>
                            <div class="flex items-center gap-6">
                                <i class="pi pi-envelope text-[#003399]"></i>
                                <p class="text-slate-900 font-black text-sm">{{ office.email }}</p>
                            </div>
                        </div>
                        <button class="w-full py-5 bg-slate-50 text-slate-900 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-[#003399] hover:text-white transition-all">
                            Voir sur la carte <i class="pi pi-external-link ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [SECTION 4] FAQ RAPIDE --- -->
    <section class="py-32 px-6 bg-white">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-24">
                <h3 class="text-5xl font-black text-slate-900 tracking-tighter italic">Réponses Immédiates</h3>
            </div>
            <div class="space-y-6">
                <div v-for="(item, idx) in quickFaqs" :key="idx" class="bg-slate-50 rounded-[2.5rem] p-8 border border-slate-100 hover:bg-white hover:shadow-xl transition-all group">
                    <div class="flex items-center gap-6">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-red-600 shadow-sm font-black">?</div>
                        <h4 class="text-xl font-black text-slate-900 italic">{{ item.q }}</h4>
                    </div>
                    <p class="mt-6 text-slate-500 font-medium leading-relaxed italic border-l-2 border-red-500 pl-6">{{ item.a }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [SECTION 5] BANNER SUPPORT --- -->
    <section class="py-24 px-6">
        <div class="max-w-[1500px] mx-auto bg-[#001A4D] rounded-lg p-6 md:p-12 relative overflow-hidden shadow-lg flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="relative z-10 space-y-6 text-center lg:text-left max-w-lg">
                <h3 class="text-4xl md:text-6xl font-black text-white leading-tight italic tracking-tighter">Support Technique <br/> <span class="text-red-500">24/7 de Proximité</span>.</h3>
                <p class="text-blue-100/60 font-medium text-xl leading-relaxed italic">Nos ingénieurs NOC basés à Goma supervisent votre réseau en temps réel.</p>
            </div>
            <div class="relative z-10 flex flex-col sm:flex-row gap-6">
                <button class="px-12 py-6 bg-white text-[#001A4D] rounded-lg font-black text-lg hover:scale-105 transition-all shadow-xl">Ouvrir un Ticket</button>
                <button class="px-12 py-6 bg-red-600 text-white rounded-lg font-black text-lg hover:bg-red-700 transition-all shadow-xl flex items-center gap-4">
                    <i class="pi pi-phone animate-bounce"></i> Appeler l'Urgence
                </button>
            </div>
            <!-- Décorations -->
            <div class="absolute top-0 right-0 w-80 h-80 bg-blue-600/20 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-red-600/10 rounded-full blur-[100px]"></div>
        </div>
    </section>
  </AppLayout>
</template>

<style scoped>
/* --- STYLES PRIME VUE PERSONNALISÉS --- */
.subnet-input {
    width: 100% !important;
    background: #f8fafc !important;
    border: 2px solid #f1f5f9 !important;
    border-radius: 1.5rem !important;
    padding: 1.25rem 1.5rem !important;
    font-weight: 700 !important;
    transition: all 0.3s ease !important;
}

.subnet-input:focus {
    background: #ffffff !important;
    border-color: #EE1C25 !important;
    box-shadow: 0 10px 30px rgba(238, 28, 37, 0.08) !important;
    outline: none !important;
}

.subnet-dropdown {
    width: 100% !important;
    background: #f8fafc !important;
    border: 2px solid #f1f5f9 !important;
    border-radius: 1.5rem !important;
    padding: 0.5rem !important;
}

.p-dropdown-panel {
    border-radius: 1.5rem !important;
    border: none !important;
    box-shadow: 0 30px 60px rgba(0,0,0,0.1) !important;
}

.country-dropdown.p-dropdown {
    border-right: 1px solid #f1f5f9 !important;
    border-radius: 1.5rem 0 0 1.5rem !important;
    background: #f8fafc !important;
    border-width: 2px !important;
    border-color: #f1f5f9 !important;
}

.p-inputgroup .p-dropdown:focus-within {
    border-color: #EE1C25 !important;
}

.country-dropdown .p-dropdown-trigger {
    width: 2rem !important;
}


@keyframes slow-zoom {
  0% { transform: scale(1); }
  100% { transform: scale(1.1); }
}
.animate-slow-zoom { animation: slow-zoom 20s linear infinite alternate; }
</style>
