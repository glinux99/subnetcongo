<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

// Définition des props avec des valeurs de secours sécurisées
const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});

const toast = useToast();

// Initialisation du formulaire Inertia
const form = useForm({
    site_name:        props.identity.site_name || '',
    site_tagline:     props.identity.site_tagline || '',
    site_description: props.identity.site_description || '',
    contact_email:    props.identity.contact_email || '',
    contact_phone:    props.identity.contact_phone || '',
    contact_address:  props.identity.contact_address || '',
    social_facebook:  props.identity.social_facebook || '',
    social_twitter:   props.identity.social_twitter || '',
    social_linkedin:  props.identity.social_linkedin || '',
    social_youtube:   props.identity.social_youtube || '',
    social_instagram: props.identity.social_instagram || '',
    // Champs pour les fichiers
    logo: null,
    favicon: null,
});

// Prévisualisation des images
const logoPreview = ref(props.identity.logo_url || null);
const faviconPreview = ref(props.identity.favicon_url || null);

const handleFileChange = (event, type) => {
    const file = event.target.files[0];
    if (!file) return;

    if (type === 'logo') {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    } else if (type === 'favicon') {
        form.favicon = file;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    // Note : Remplacez 'web.theme.save' par le vrai nom de votre route
    form.post(route('web.theme.save'), {
        preserveScroll: true,
        forceFormData: true, // IMPORTANT : Requis pour l'envoi de fichiers (multipart/form-data)
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Les paramètres du thème ont été mis à jour.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les erreurs dans le formulaire.', life: 4000 });
        }
    });
};
</script>

<template>
    <Head title="Configuration du Thème" />

    <AppLayout>
        <div class="min-h-screen pb-24 font-sans">
            <!-- Header de la page -->
            <div class="bg-white border-b border-slate-200 px-6 py-8 sm:px-10 mb-8 shadow-sm">
                <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Identité & Thème</h1>
                        <p class="text-slate-500 text-sm mt-1">Gérez l'apparence globale, le logo et les informations de contact de votre plateforme.</p>
                    </div>
                </div>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="submit" class="max mx-auto px-6 sm:px-10 space-y-8">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- COLONNE GAUCHE (Identité & Réseaux) -->
                    <div class="lg:col-span-8 space-y-8">

                        <!-- Block 1 : Informations Générales -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-id-card text-indigo-500"></i> Informations Générales
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nom du site</label>
                                    <input v-model="form.site_name" type="text" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="Ex: SubnetCongo" />
                                    <p v-if="form.errors.site_name" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Slogan (Tagline)</label>
                                    <input v-model="form.site_tagline" type="text" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="Ex: Leader de la formation IT" />
                                    <p v-if="form.errors.site_tagline" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_tagline }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Description (SEO)</label>
                                    <textarea v-model="form.site_description" rows="4" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700 resize-none" placeholder="Décrivez votre activité pour les moteurs de recherche..."></textarea>
                                    <p v-if="form.errors.site_description" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Block 2 : Réseaux Sociaux -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-share-alt text-indigo-500"></i> Réseaux Sociaux
                                </h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Facebook</label>
                                    <div class="relative">
                                        <i class="pi pi-facebook absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input v-model="form.social_facebook" type="url" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="https://facebook.com/..." />
                                    </div>
                                    <p v-if="form.errors.social_facebook" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.social_facebook }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">LinkedIn</label>
                                    <div class="relative">
                                        <i class="pi pi-linkedin absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input v-model="form.social_linkedin" type="url" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="https://linkedin.com/..." />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Twitter / X</label>
                                    <div class="relative">
                                        <i class="pi pi-twitter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input v-model="form.social_twitter" type="url" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="https://twitter.com/..." />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Instagram</label>
                                    <div class="relative">
                                        <i class="pi pi-instagram absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input v-model="form.social_instagram" type="url" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="https://instagram.com/..." />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLONNE DROITE (Médias & Contact) -->
                    <div class="lg:col-span-4 space-y-8">

                        <!-- Block 3 : Visuels (Logos) -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-image text-indigo-500"></i> Médias
                                </h2>
                            </div>
                            <div class="p-6 space-y-6">

                                <!-- Logo principal -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-3">Logo Principal</label>
                                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:bg-slate-50 transition-colors relative">
                                        <img v-if="logoPreview" :src="logoPreview" class="h-20 mx-auto object-contain mb-3" alt="Logo preview" />
                                        <div v-else class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="pi pi-image text-xl text-slate-400"></i>
                                        </div>
                                        <label class="cursor-pointer">
                                            <span class="text-sm font-bold text-indigo-600 hover:text-indigo-700">Changer le logo</span>
                                            <input type="file" class="hidden" accept="image/*" @change="e => handleFileChange(e, 'logo')" />
                                        </label>
                                    </div>
                                    <p v-if="form.errors.logo" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.logo }}</p>
                                </div>

                                <!-- Favicon -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-3">Favicon</label>
                                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:bg-slate-50 transition-colors relative">
                                        <img v-if="faviconPreview" :src="faviconPreview" class="h-10 w-10 mx-auto object-contain mb-3 rounded" alt="Favicon preview" />
                                        <label class="cursor-pointer">
                                            <span class="text-sm font-bold text-indigo-600 hover:text-indigo-700">Changer l'icône</span>
                                            <input type="file" class="hidden" accept="image/x-icon,image/png" @change="e => handleFileChange(e, 'favicon')" />
                                        </label>
                                    </div>
                                    <p v-if="form.errors.favicon" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.favicon }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Block 4 : Contact -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-envelope text-indigo-500"></i> Coordonnées
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Email Officiel</label>
                                    <input v-model="form.contact_email" type="email" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="contact@domaine.com" />
                                    <p v-if="form.errors.contact_email" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.contact_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Téléphone</label>
                                    <input v-model="form.contact_phone" type="text" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700" placeholder="+243 000 000 000" />
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Adresse Physique</label>
                                    <textarea v-model="form.contact_address" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none text-slate-700 resize-none" placeholder="Votre adresse complète..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- STICKY BOTTOM ACTION BAR -->
                <div class="fixed bottom-0 left-0 right-0 md:left-[16rem] lg:left-[18rem] bg-white border-t border-slate-200 p-4 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)] z-40 flex justify-end">
                    <div class="max-w-5xl mx-auto w-full flex items-center justify-end px-6 gap-4">
                        <span v-if="form.isDirty" class="text-amber-500 text-sm font-bold animate-pulse mr-auto">
                            Modifications non enregistrées
                        </span>

                        <button type="button" @click="form.reset()" :disabled="!form.isDirty" class="px-6 py-2.5 text-slate-600 font-bold hover:bg-slate-100 rounded-xl transition-colors disabled:opacity-50">
                            Annuler
                        </button>

                        <button type="submit" :disabled="form.processing || !form.isDirty" class="px-8 py-2.5 bg-[#0B1221] hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-sm"></i>
                            <i v-else class="pi pi-save text-sm"></i>
                            Enregistrer les paramètres
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
