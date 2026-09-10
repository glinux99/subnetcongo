<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import { ref } from 'vue';

const props = defineProps({
    slides:       { type: Array, default: () => [] },
    technologies: { type: Array, default: () => [] },
    stats:        { type: Array, default: () => [] },
});

const tab = ref(0);
const editingSlide = ref(null);
const editingTech = ref(null);
const editingStat = ref(null);
const showSlideDialog = ref(false);
const showTechDialog = ref(false);
const showStatDialog = ref(false);

// Previews pour les images
const bgImagePreview   = ref(null);
const mainImagePreview = ref(null);

// ── FONCTION DE SECURISATION FORM (Fix Error setData) ──
const populateForm = (form, data) => {
    form.clearErrors();
    Object.keys(data).forEach(key => {
        if (key in form) form[key] = data[key];
    });
};

// ── SLIDES CRUD ─────────────────────────────────────────────────────
const slideForm = useForm({
    badge: '', title: '', highlight: '', description: '',
    bg_image: '', main_image: '', icon: 'pi-globe',
    card_title: '', card_sub: '', cta_label: '', cta_url: '',
    position: 1, is_active: true,
    bg_image_file: null, main_image_file: null,
});

const openNewSlide = () => {
    slideForm.reset();
    editingSlide.value = null;
    bgImagePreview.value = null;
    mainImagePreview.value = null;
    showSlideDialog.value = true;
};

const openEditSlide = (s) => {
    editingSlide.value = s;
    populateForm(slideForm, s);
    bgImagePreview.value = s.bg_image;
    mainImagePreview.value = s.main_image;
    showSlideDialog.value = true;
};

const saveSlide = () => {
    const url = editingSlide.value 
        ? route('admin.landing.slides.update', editingSlide.value.id) 
        : route('admin.landing.slides.store');
    
    // On utilise POST avec _method PUT pour les fichiers
    slideForm.transform((data) => ({
        ...data,
        _method: editingSlide.value ? 'PUT' : 'POST',
    })).post(url, {
        forceFormData: true,
        onSuccess: () => { showSlideDialog.value = false; slideForm.reset(); }
    });
};

const deleteSlide = (s) => { if(confirm('Supprimer ce slide ?')) router.delete(route('admin.landing.slides.destroy', s.id)); };
const toggleSlide = (s) => router.post(route('admin.landing.slides.toggle', s.id));

// ── TECHNOLOGIES CRUD ──────────────────────────────────────────────
const techForm = useForm({
    name: '', slug: '', icon: '', color: '#E11D48',
    category: '', description: '', position: 1, is_active: true,
});

const openNewTech = () => { techForm.reset(); editingTech.value = null; showTechDialog.value = true; };
const openEditTech = (t) => {
    editingTech.value = t;
    populateForm(techForm, t);
    showTechDialog.value = true;
};

const saveTech = () => {
    const url = editingTech.value ? route('admin.landing.tech.update', editingTech.value.id) : route('admin.landing.tech.store');
    const method = editingTech.value ? 'put' : 'post';
    techForm[method](url, { onSuccess: () => { showTechDialog.value = false; techForm.reset(); }});
};

const deleteTech = (t) => { if(confirm('Supprimer cette technologie ?')) router.delete(route('admin.landing.tech.destroy', t.id)); };
const toggleTech = (t) => router.post(route('admin.landing.tech.toggle', t.id));

// ── STATS CRUD (FIXED) ─────────────────────────────────────────────
const statForm = useForm({
    label: '', value: '', icon: 'pi-chart-bar', color: '#6366F1', position: 1, is_active: true
});

const openNewStat = () => { statForm.reset(); editingStat.value = null; showStatDialog.value = true; };
const openEditStat = (s) => {
    editingStat.value = s;
    populateForm(statForm, s); // Utilisation de la fonction securisée
    showStatDialog.value = true;
};

const saveStat = () => {
    const url = editingStat.value ? route('admin.landing.stats.update', editingStat.value.id) : route('admin.landing.stats.store');
    const method = editingStat.value ? 'put' : 'post';
    statForm[method](url, { onSuccess: () => { showStatDialog.value = false; statForm.reset(); }});
};

const deleteStat = (s) => { if(confirm('Supprimer cette statistique ?')) router.delete(route('admin.landing.stats.destroy', s.id)); };
const toggleStat = (s) => router.post(route('admin.landing.stats.toggle', s.id));

// Helpers images
const handleBgPick = (e) => { slideForm.bg_image_file = e.target.files[0]; bgImagePreview.value = URL.createObjectURL(e.target.files[0]); };
const handleMainPick = (e) => { slideForm.main_image_file = e.target.files[0]; mainImagePreview.value = URL.createObjectURL(e.target.files[0]); };

</script>

<template>
    <Head title="Configuration Landing Page" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            
            <!-- HEADER V30 (Saas Style) -->
            <div class="bg-[#0F172A] pt-12 pb-24 px-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-red-600/10 rounded-full blur-[120px]"></div>
                <div class="max-w-[1400px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-rose-400 rounded-xl flex items-center justify-center shadow-2xl rotate-3">
                            <i class="pi pi-desktop text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tighter italic uppercase">Landing <span class="text-red-500">Editor</span></h1>
                            <p class="text-slate-400 text-sm font-medium">Contrôlez l'expérience visuelle de SubnetCongo en temps réel.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 bg-white/5 p-1.5 rounded-xl border border-white/10 backdrop-blur-xl">
                        <button v-for="(l, i) in ['Hero', 'Tech', 'Stats']" :key="i" @click="tab = i"
                                :class="['px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all', tab === i ? 'bg-red-600 text-white shadow-lg' : 'text-slate-400 hover:text-white']">
                            {{ l }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTAINER -->
            <div class="max-w-[1400px] mx-auto px-6 -mt-12 relative z-20">
                
                <!-- ── SLIDES SECTION ── -->
                <div v-if="tab === 0" class="space-y-6 animate-fade-in">
                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] shadow-sm border border-slate-200">
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Carrousel Principal</h3>
                            <p class="text-xs text-slate-500 mt-1 italic">Images haute résolution recommandées (1920x1080).</p>
                        </div>
                        <Button label="Nouveau Slide" icon="pi pi-plus" @click="openNewSlide" class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-6" />
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div v-for="s in slides" :key="s.id" class="group bg-white rounded-[1rem] border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl transition-all duration-500">
                            <div class="relative h-48 bg-slate-100">
                                <img :src="s.bg_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px] flex flex-col justify-end p-6">
                                    <Tag v-if="s.badge" :value="s.badge" class="!w-fit !mb-2 !bg-red-600 !text-[8px] !font-black !px-2" />
                                    <h4 class="text-white font-black text-xl italic uppercase tracking-tighter">{{ s.title }}</h4>
                                </div>
                                <div class="absolute top-4 right-4 flex gap-2">
                                    <button @click="openEditSlide(s)" class="w-10 h-10 bg-white/90 hover:bg-white rounded-xl flex items-center justify-center shadow-lg transition-colors text-slate-700"><i class="pi pi-pencil"></i></button>
                                    <button @click="deleteSlide(s)" class="w-10 h-10 bg-red-600 hover:bg-red-700 rounded-xl flex items-center justify-center shadow-lg transition-colors text-white"><i class="pi pi-trash"></i></button>
                                </div>
                            </div>
                            <div class="p-6 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch :modelValue="s.is_active" @update:modelValue="toggleSlide(s)" />
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Position: {{ s.position }}</span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase italic">ID: #{{ s.id }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TECHNOLOGIES SECTION ── -->
                <div v-if="tab === 1" class="space-y-6">
                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] shadow-sm border border-slate-200">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Stack Technique Enseignée</h3>
                        <Button label="Ajouter Technologie" icon="pi pi-plus" @click="openNewTech" class="!bg-red-600 !border-none !rounded-xl" />
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div v-for="t in technologies" :key="t.id" class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-lg transition-all group relative overflow-hidden">
                            <div :style="{ backgroundColor: t.color + '15' }" class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 space-y-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shadow-inner border border-white" :style="{ backgroundColor: t.color + '20', color: t.color }">
                                    <i v-if="t.icon?.startsWith('pi-')" :class="`pi ${t.icon}`"></i>
                                    <span v-else>{{ t.icon || '🛠️' }}</span>
                                </div>
                                <h4 class="font-black text-slate-800 tracking-tight italic uppercase">{{ t.name }}</h4>
                                <div class="flex justify-between items-center pt-2 border-t border-slate-50">
                                    <div class="flex gap-2">
                                        <button @click="openEditTech(t)" class="text-slate-400 hover:text-red-500 transition-colors"><i class="pi pi-pencil text-xs"></i></button>
                                        <button @click="deleteTech(t)" class="text-slate-400 hover:text-red-600 transition-colors"><i class="pi pi-trash text-xs"></i></button>
                                    </div>
                                    <ToggleSwitch :modelValue="t.is_active" @update:modelValue="toggleTech(t)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── STATS SECTION ── -->
                <div v-if="tab === 2" class="space-y-6">
                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] shadow-sm border border-slate-200">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kpis & Compteurs</h3>
                        <Button label="Nouvelle Stat" icon="pi pi-plus" @click="openNewStat" class="!bg-red-600 !border-none !rounded-xl" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div v-for="s in stats" :key="s.id" class="p-8 bg-slate-900 rounded-[1rem] border border-white/5 relative overflow-hidden group shadow-2xl">
                            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-red-500/20 transition-all"></div>
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <div class="w-14 h-14 rounded-xl bg-white/5 flex items-center justify-center mb-4 border border-white/10" :style="{ color: s.color }">
                                    <i :class="`pi ${s.icon} text-2xl`"></i>
                                </div>
                                <h2 class="text-4xl font-black mb-1 italic tracking-tighter" :style="{ color: s.color }">{{ s.value }}</h2>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-6">{{ s.label }}</p>
                                
                                <div class="flex gap-3 pt-4 border-t border-white/5 w-full justify-center items-center">
                                    <button @click="openEditStat(s)" class="text-slate-400 hover:text-white"><i class="pi pi-pencil"></i></button>
                                    <button @click="deleteStat(s)" class="text-slate-400 hover:text-red-500"><i class="pi pi-trash"></i></button>
                                    <ToggleSwitch :modelValue="s.is_active" @update:modelValue="toggleStat(s)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── DIALOG SLIDE V30 ── -->
            <Dialog v-model:visible="showSlideDialog" modal header="Configuration Slide Hero" :style="{ width: '800px' }" class="!rounded-[1rem]">
                <form @submit.prevent="saveSlide" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest">Badge</label><InputText v-model="slideForm.badge" class="w-full !rounded-xl !p-3 !bg-slate-50" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest">Titre Principal</label><InputText v-model="slideForm.title" class="w-full !rounded-xl !p-3 !bg-slate-50" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest">Highlight (Rouge)</label><InputText v-model="slideForm.highlight" class="w-full !rounded-xl !p-3 !bg-slate-50 text-red-600" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest">Description</label><Textarea v-model="slideForm.description" rows="4" class="w-full !rounded-xl !p-3 !bg-slate-50" /></div>
                    </div>
                    <div class="space-y-4">
                        <div class="p-4 rounded-3xl border-2 border-dashed border-slate-100 bg-slate-50 flex flex-col items-center">
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-4 block tracking-widest">Image de fond</label>
                            <img v-if="bgImagePreview" :src="bgImagePreview" class="w-full h-32 object-cover rounded-xl mb-4 border-4 border-white shadow-xl" />
                            <input type="file" @change="handleBgPick" class="text-xs font-bold text-slate-500" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Label CTA</label><InputText v-model="slideForm.cta_label" class="w-full !rounded-xl" /></div>
                            <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">URL CTA</label><InputText v-model="slideForm.cta_url" class="w-full !rounded-xl" /></div>
                        </div>
                    </div>
                </form>
                <template #footer>
                    <div class="flex justify-end gap-3 p-4 border-t border-slate-50">
                        <Button label="Annuler" text @click="showSlideDialog = false" class="!text-slate-400 !font-bold" />
                        <Button :label="editingSlide ? 'Mettre à jour' : 'Sauvegarder'" icon="pi pi-check" :loading="slideForm.processing" @click="saveSlide" class="!bg-red-600 !border-none !rounded-xl !px-10" />
                    </div>
                </template>
            </Dialog>

            <!-- DIALOGS TECH & STATS (Simplified) -->
            <Dialog v-model:visible="showTechDialog" modal header="Paramètres Technologie" :style="{ width: '500px' }" class="!rounded-[1rem]">
                 <form @submit.prevent="saveTech" class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Nom</label><InputText v-model="techForm.name" class="w-full !rounded-xl" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Couleur</label><InputText v-model="techForm.color" type="color" class="w-full !h-12 !p-1 !rounded-xl" /></div>
                    </div>
                    <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Icône (pi-* ou emoji)</label><InputText v-model="techForm.icon" class="w-full !rounded-xl" placeholder="🚀 ou pi-database" /></div>
                    <Button label="Enregistrer" :loading="techForm.processing" @click="saveTech" class="w-full !bg-red-600 !rounded-xl !py-4" />
                 </form>
            </Dialog>

            <Dialog v-model:visible="showStatDialog" modal header="Paramètres Statistique" :style="{ width: '450px' }" class="!rounded-[1rem]">
                <form @submit.prevent="saveStat" class="p-6 space-y-5 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-2xl border border-slate-100" :style="{ color: statForm.color }">
                        <i :class="`pi ${statForm.icon}`"></i>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Valeur</label><InputText v-model="statForm.value" class="w-full !rounded-xl" placeholder="Ex: 850+" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Icône</label><InputText v-model="statForm.icon" class="w-full !rounded-xl" /></div>
                    </div>
                    <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Libellé</label><InputText v-model="statForm.label" class="w-full !rounded-xl" /></div>
                    <Button label="Confirmer" :loading="statForm.processing" @click="saveStat" class="w-full !bg-red-600 !rounded-xl !py-4" />
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
:deep(.p-dialog-header) { padding: 2rem 2rem 1rem 2rem; }
:deep(.p-dialog-title) { font-weight: 900; text-transform: uppercase; letter-spacing: -0.05em; font-style: italic; }
:deep(.p-inputtext) { border: 1px solid #E2E8F0; }
:deep(.p-inputtext:focus) { border-color: #E11D48; box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1); }
</style>