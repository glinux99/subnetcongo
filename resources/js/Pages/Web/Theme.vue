<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

// Importation des composants PrimeVue pour uniformiser le design
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Breadcrumb from 'primevue/breadcrumb';
import Toast from 'primevue/toast';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

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
        <Toast position="top-right" />

        <div class="page-bg min-h-screen font-sans pb-32">
            
            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE (Dark Intelligence Hub Style)                                   -->
            <!-- ============================================================================== -->
            <div class="flex flex-col gap-2 mb-12 px-4 md:px-6 pt-4">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Administration', icon: 'pi pi-cog' }, { label: 'Identité & Thème' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- Bannière Sombre avec padding bas (pb-28) pour le chevauchement -->
                <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="space-y-4 max-w-3xl">
                            <!-- Badge -->
                            <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                                Global Settings
                            </span>

                            <!-- Titre Principal (Italique, gras, 2 couleurs) -->
                            <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                                <span class="text-white">Identité &</span>
                                <span class="text-[#E92B2B]">Thème Visuel</span>
                            </h1>

                            <!-- Sous-titre -->
                            <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                                Personnalisez l'apparence globale de la plateforme. Gérez votre logo, les informations de contact et les liens de vos réseaux sociaux pour renforcer votre marque.
                            </p>
                        </div>

                        <!-- Partie Droite (Boutons d'action rapides si besoin) -->
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <Button 
                                label="Voir la vitrine" 
                                icon="pi pi-external-link" 
                                class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                                as="a" 
                                href="/" 
                                target="_blank"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- FORMULAIRE OVERLAPPING (Chevauchement)                                         -->
            <!-- ============================================================================== -->
            <form @submit.prevent="submit" class="-mt-16 relative z-20 px-4 md:px-6 max-w-[100rem] mx-auto space-y-8">

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 xl:gap-8">

                    <!-- COLONNE GAUCHE (Identité & Réseaux) -->
                    <div class="xl:col-span-8 space-y-6">

                        <!-- Block 1 : Informations Générales -->
                        <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-md bg-indigo-50 flex items-center justify-center">
                                        <i class="pi pi-id-card text-indigo-500"></i>
                                    </div>
                                    Informations Générales
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nom du site</label>
                                    <InputText v-model="form.site_name" class="w-full bg-slate-50 border-slate-200 font-medium" placeholder="Ex: SubnetCongo" />
                                    <p v-if="form.errors.site_name" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Slogan (Tagline)</label>
                                    <InputText v-model="form.site_tagline" class="w-full bg-slate-50 border-slate-200 font-medium" placeholder="Ex: Leader de la formation IT" />
                                    <p v-if="form.errors.site_tagline" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_tagline }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Description (SEO)</label>
                                    <Textarea v-model="form.site_description" rows="4" class="w-full bg-slate-50 border-slate-200 font-medium resize-none" placeholder="Décrivez votre activité pour les moteurs de recherche..."></Textarea>
                                    <p v-if="form.errors.site_description" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.site_description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Block 2 : Réseaux Sociaux -->
                        <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-md bg-blue-50 flex items-center justify-center">
                                        <i class="pi pi-share-alt text-blue-500"></i>
                                    </div>
                                    Réseaux Sociaux
                                </h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Facebook</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-facebook text-slate-400" />
                                        <InputText v-model="form.social_facebook" type="url" placeholder="https://facebook.com/..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                    <p v-if="form.errors.social_facebook" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.social_facebook }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">LinkedIn</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-linkedin text-slate-400" />
                                        <InputText v-model="form.social_linkedin" type="url" placeholder="https://linkedin.com/..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Twitter / X</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-twitter text-slate-400" />
                                        <InputText v-model="form.social_twitter" type="url" placeholder="https://twitter.com/..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Instagram</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-instagram text-slate-400" />
                                        <InputText v-model="form.social_instagram" type="url" placeholder="https://instagram.com/..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">YouTube</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-youtube text-slate-400" />
                                        <InputText v-model="form.social_youtube" type="url" placeholder="https://youtube.com/..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLONNE DROITE (Médias & Contact) -->
                    <div class="xl:col-span-4 space-y-6">

                        <!-- Block 3 : Visuels (Logos) -->
                        <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-md bg-amber-50 flex items-center justify-center">
                                        <i class="pi pi-image text-amber-500"></i>
                                    </div>
                                    Médias
                                </h2>
                            </div>
                            <div class="p-6 space-y-6">

                                <!-- Logo principal -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-3">Logo Principal</label>
                                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:bg-slate-50 transition-colors relative">
                                        <img v-if="logoPreview" :src="logoPreview" class="h-24 mx-auto object-contain mb-3 drop-shadow-sm" alt="Logo preview" />
                                        <div v-else class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="pi pi-image text-xl text-slate-400"></i>
                                        </div>
                                        <label class="cursor-pointer inline-block mt-2">
                                            <span class="text-sm font-bold px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-colors">Changer le logo</span>
                                            <input type="file" class="hidden" accept="image/*" @change="e => handleFileChange(e, 'logo')" />
                                        </label>
                                    </div>
                                    <p v-if="form.errors.logo" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.logo }}</p>
                                </div>

                                <!-- Favicon -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-3">Favicon</label>
                                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:bg-slate-50 transition-colors relative flex flex-col items-center justify-center">
                                        <img v-if="faviconPreview" :src="faviconPreview" class="h-10 w-10 object-contain mb-3 rounded" alt="Favicon preview" />
                                        <label class="cursor-pointer">
                                            <span class="text-xs font-bold text-indigo-600 hover:text-indigo-700 underline">Changer l'icône</span>
                                            <input type="file" class="hidden" accept="image/x-icon,image/png" @change="e => handleFileChange(e, 'favicon')" />
                                        </label>
                                    </div>
                                    <p v-if="form.errors.favicon" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.favicon }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Block 4 : Contact -->
                        <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-md bg-emerald-50 flex items-center justify-center">
                                        <i class="pi pi-envelope text-emerald-500"></i>
                                    </div>
                                    Coordonnées
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Email Officiel</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-at text-slate-400" />
                                        <InputText v-model="form.contact_email" type="email" placeholder="contact@domaine.com" class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                    <p v-if="form.errors.contact_email" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.contact_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Téléphone</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-phone text-slate-400" />
                                        <InputText v-model="form.contact_phone" type="text" placeholder="+243 000 000 000" class="w-full bg-slate-50 border-slate-200 font-medium" />
                                    </IconField>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-2">Adresse Physique</label>
                                    <Textarea v-model="form.contact_address" rows="3" class="w-full bg-slate-50 border-slate-200 font-medium resize-none" placeholder="Votre adresse complète..."></Textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- STICKY BOTTOM ACTION BAR                                                       -->
                <!-- ============================================================================== -->
                <div class="fixed bottom-0 left-0 right-0 md:left-[16rem] lg:left-[18rem] bg-white/80 backdrop-blur-md border-t border-slate-200 p-4 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)] z-40 flex justify-end transition-all">
                    <div class="w-full flex items-center justify-end px-6 gap-4">
                        <span v-if="form.isDirty" class="text-amber-600 text-sm font-bold animate-pulse mr-auto flex items-center gap-2">
                            <i class="pi pi-exclamation-circle"></i> Modifications non enregistrées
                        </span>

                        <Button 
                            type="button" 
                            label="Annuler" 
                            @click="form.reset()" 
                            :disabled="!form.isDirty" 
                            class="p-button-text p-button-secondary font-bold text-slate-600 px-6" 
                        />
                        
                        <Button 
                            type="submit" 
                            label="Enregistrer le thème" 
                            :icon="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-save'" 
                            :disabled="form.processing || !form.isDirty" 
                            class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white font-bold shadow-lg shadow-red-900/50 transition-colors px-8 py-2.5" 
                        />
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Overrides UI Forms PrimeVue pour un look moderne et clean (Identique à la banque de questions) */
:deep(.p-inputtext), :deep(.p-textarea) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-textarea:enabled:focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
    background-color: #ffffff;
}
</style>