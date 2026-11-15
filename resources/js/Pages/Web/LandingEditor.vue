<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
    slideForm.position = null;
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
        onSuccess: () => { showSlideDialog.value = false; slideForm.reset(); slideForm.clearErrors(); bgImagePreview.value = null; mainImagePreview.value = null; }
    });
};

const confirmAction = useConfirmAction();
const deleteSlide = (s) => confirmAction({ message: t('pages.web_landing_editor.confirmDeleteSlide'), acceptLabel: t('pages.web_landing_editor.acceptDelete'), danger: true, accept: () => router.delete(route('admin.landing.slides.destroy', s.id)) });
const toggleSlide = (s) => router.post(route('admin.landing.slides.toggle', s.id));

const moveSlide = (index, direction) => {
    const list = [...props.slides];
    const target = index + direction;
    if (target < 0 || target >= list.length) return;
    [list[index], list[target]] = [list[target], list[index]];
    router.post(route('admin.landing.slides.reorder'), { ids: list.map(s => s.id) }, { preserveScroll: true });
};

// ── TECHNOLOGIES CRUD ──────────────────────────────────────────────
const techForm = useForm({
    name: '', slug: '', icon: '', color: '#E11D48',
    category: '', description: '', position: 1, is_active: true,
});

const openNewTech = () => { techForm.reset(); techForm.position = null; editingTech.value = null; showTechDialog.value = true; };
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

const deleteTech = (t2) => confirmAction({ message: t('pages.web_landing_editor.confirmDeleteTech'), acceptLabel: t('pages.web_landing_editor.acceptDelete'), danger: true, accept: () => router.delete(route('admin.landing.tech.destroy', t2.id)) });
const toggleTech = (t) => router.post(route('admin.landing.tech.toggle', t.id));

// ── STATS CRUD (FIXED) ─────────────────────────────────────────────
const statForm = useForm({
    label: '', value: '', icon: 'pi-chart-bar', color: '#6366F1', position: 1, is_active: true
});

const openNewStat = () => { statForm.reset(); statForm.position = null; editingStat.value = null; showStatDialog.value = true; };
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

const deleteStat = (s) => confirmAction({ message: t('pages.web_landing_editor.confirmDeleteStat'), acceptLabel: t('pages.web_landing_editor.acceptDelete'), danger: true, accept: () => router.delete(route('admin.landing.stats.destroy', s.id)) });
const toggleStat = (s) => router.post(route('admin.landing.stats.toggle', s.id));

// Helpers images
const handleBgPick = (e) => { const file = e.target.files?.[0]; if (!file) return; slideForm.bg_image_file = file; bgImagePreview.value = URL.createObjectURL(file); };
const handleMainPick = (e) => { const file = e.target.files?.[0]; if (!file) return; slideForm.main_image_file = file; mainImagePreview.value = URL.createObjectURL(file); };

</script>

<template>
    <Head :title="t('pages.web_landing_editor.title')" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            
            <!-- HEADER V30 (Saas Style) -->
            <div class="bg-[#0F172A] pt-12 pb-24 px-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-red-600/10 rounded-full blur-[120px]"></div>
                <div class="max-w-[1400px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-400 rounded-xl flex items-center justify-center shadow-2xl rotate-3">
                            <i class="pi pi-desktop text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tighter italic uppercase">Landing <span class="text-red-500">Editor</span></h1>
                            <p class="text-slate-400 text-sm font-medium">{{ t('pages.web_landing_editor.subtitle') }}</p>
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
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.web_landing_editor.mainCarousel') }}</h3>
                            <p class="text-xs text-slate-500 mt-1 italic">{{ t('pages.web_landing_editor.resolutionTip') }}</p>
                        </div>
                        <Button :label="t('pages.web_landing_editor.newSlide')" icon="pi pi-plus" @click="openNewSlide" class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-6" />
                    </div>

                    <div v-if="!slides.length" class="bg-white rounded-[1rem] border border-dashed border-slate-300 p-12 text-center">
                        <i class="pi pi-images text-4xl text-slate-300 block mb-4"></i>
                        <p class="font-bold text-slate-600">{{ t('pages.web_landing_editor.noSlides') }}</p>
                        <p class="text-sm text-slate-400 mt-1">{{ t('pages.web_landing_editor.noSlidesHint') }}</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div v-for="(s, index) in slides" :key="s.id" class="group bg-white rounded-[1rem] border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl transition-all duration-500">
                             <div class="relative h-48 bg-slate-100">
                                <img v-if="s.bg_image" :src="s.bg_image" :alt="s.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900 text-slate-500 text-xs font-bold uppercase tracking-widest">{{ t('pages.web_landing_editor.noBgImage') }}</div>
                                <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px] flex flex-col justify-end p-6">
                                    <Tag v-if="s.badge" :value="s.badge" class="!w-fit !mb-2 !bg-red-600 !text-[8px] !font-black !px-2" />
                                    <h4 class="text-white font-black text-xl italic uppercase tracking-tighter">{{ s.title }}</h4>
                                </div>
                                <div class="absolute top-4 right-4 flex gap-2">
                                    <button @click="openEditSlide(s)" class="w-10 h-10 bg-white/90 hover:bg-white rounded-xl flex items-center justify-center shadow-lg transition-colors text-slate-700"><i class="pi pi-pencil"></i></button>
                                    <button @click="deleteSlide(s)" class="w-10 h-10 bg-red-600 hover:bg-red-700 rounded-xl flex items-center justify-center shadow-lg transition-colors text-white"><i class="pi pi-trash"></i></button>
                                </div>
                            </div>
                            <div class="p-6 flex justify-between items-center gap-4">
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch :modelValue="s.is_active" @update:modelValue="toggleSlide(s)" />
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ t('pages.web_landing_editor.position') }}: {{ s.position }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col gap-1">
                                        <button @click="moveSlide(index, -1)" :disabled="index === 0" :aria-label="t('pages.web_landing_editor.moveSlideUp')" class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 transition-all hover:bg-slate-100 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"><i class="pi pi-chevron-up text-[10px]"></i></button>
                                        <button @click="moveSlide(index, 1)" :disabled="index === slides.length - 1" :aria-label="t('pages.web_landing_editor.moveSlideDown')" class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 transition-all hover:bg-slate-100 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"><i class="pi pi-chevron-down text-[10px]"></i></button>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase italic">ID: #{{ s.id }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TECHNOLOGIES SECTION ── -->
                <div v-if="tab === 1" class="space-y-6">
                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] shadow-sm border border-slate-200">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.web_landing_editor.techStack') }}</h3>
                        <Button :label="t('pages.web_landing_editor.addTech')" icon="pi pi-plus" @click="openNewTech" class="!bg-red-600 !border-none !rounded-xl" />
                    </div>

                    <div v-if="!technologies.length" class="bg-white rounded-[1rem] border border-dashed border-slate-300 p-12 text-center">
                        <i class="pi pi-sitemap text-4xl text-slate-300 block mb-4"></i>
                        <p class="font-bold text-slate-600">{{ t('pages.web_landing_editor.noTech') }}</p>
                        <p class="text-sm text-slate-400 mt-1">{{ t('pages.web_landing_editor.noTechHint') }}</p>
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
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.web_landing_editor.kpis') }}</h3>
                        <Button :label="t('pages.web_landing_editor.newStat')" icon="pi pi-plus" @click="openNewStat" class="!bg-red-600 !border-none !rounded-xl" />
                    </div>

                    <div v-if="!stats.length" class="bg-white rounded-[1rem] border border-dashed border-slate-300 p-12 text-center">
                        <i class="pi pi-chart-bar text-4xl text-slate-300 block mb-4"></i>
                        <p class="font-bold text-slate-600">{{ t('pages.web_landing_editor.noStats') }}</p>
                        <p class="text-sm text-slate-400 mt-1">{{ t('pages.web_landing_editor.noStatsHint') }}</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                        <div v-for="s in stats" :key="s.id" class="p-4 sm:p-6 bg-slate-900 rounded-[1rem] border border-white/5 relative overflow-hidden group shadow-2xl">
                            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-red-500/20 transition-all"></div>
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <div class="w-14 h-14 rounded-xl bg-white/5 flex items-center justify-center mb-4 border border-white/10" :style="{ color: s.color }">
                                    <i :class="`pi ${s.icon} text-2xl`"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-black mb-1 italic tracking-tighter" :style="{ color: s.color }">{{ s.value }}</h2>
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
            <Dialog v-model:visible="showSlideDialog" modal :header="t('pages.web_landing_editor.slideDialogTitle')" :style="{ width: 'min(860px, 96vw)' }" class="!rounded-[1rem]">
                <form @submit.prevent="saveSlide" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
                    <div v-if="Object.keys(slideForm.errors).length" class="md:col-span-2 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl px-4 py-3" role="alert">
                        <p class="uppercase tracking-widest text-[10px] mb-1">{{ t('pages.web_landing_editor.errorsToFix') }}</p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(message, field) in slideForm.errors" :key="field">{{ message }}</li>
                        </ul>
                    </div>

                    <div class="space-y-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest" for="slide-badge">{{ t('pages.web_landing_editor.fieldBadge') }}</label><InputText id="slide-badge" v-model="slideForm.badge" class="w-full !rounded-xl !p-3 !bg-slate-50" :placeholder="t('pages.web_landing_editor.badgePlaceholder')" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest" for="slide-title">{{ t('pages.web_landing_editor.fieldTitle') }}</label><InputText id="slide-title" v-model="slideForm.title" class="w-full !rounded-xl !p-3 !bg-slate-50" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest" for="slide-highlight">{{ t('pages.web_landing_editor.fieldHighlight') }}</label><InputText id="slide-highlight" v-model="slideForm.highlight" class="w-full !rounded-xl !p-3 !bg-slate-50 text-red-600" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest" for="slide-description">{{ t('pages.web_landing_editor.fieldDescription') }}</label><Textarea id="slide-description" v-model="slideForm.description" rows="4" class="w-full !rounded-xl !p-3 !bg-slate-50" /></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="slide-cta-label">{{ t('pages.web_landing_editor.fieldCtaLabel') }}</label><InputText id="slide-cta-label" v-model="slideForm.cta_label" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.ctaLabelPlaceholder')" /></div>
                            <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="slide-cta-url">URL CTA</label><InputText id="slide-cta-url" v-model="slideForm.cta_url" class="w-full !rounded-xl" placeholder="/academie ou https://…" /></div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 block mb-2 tracking-widest" for="slide-position">{{ t('pages.web_landing_editor.fieldPosition') }}</label>
                            <input id="slide-position" type="number" min="1" v-model.number="slideForm.position" class="w-28 !rounded-xl !p-3 !bg-slate-50 border border-slate-200 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-500" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 rounded-3xl border-2 border-dashed border-slate-100 bg-slate-50 flex flex-col items-center">
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-3 block tracking-widest" for="slide-bg-file">{{ t('pages.web_landing_editor.bgImage') }}</label>
                            <img v-if="bgImagePreview" :src="bgImagePreview" :alt="t('pages.web_landing_editor.bgPreviewAlt')" class="w-full h-32 object-cover rounded-xl mb-3 border-4 border-white shadow-xl" />
                            <input id="slide-bg-file" type="file" accept="image/jpeg,image/png,image/webp,image/avif" @change="handleBgPick" class="text-xs font-bold text-slate-500" />
                            <InputText v-model="slideForm.bg_image" class="w-full !rounded-xl !p-2 !mt-3 !text-xs !bg-white" :placeholder="t('pages.web_landing_editor.bgImageUrlPlaceholder')" />
                        </div>

                        <div class="p-4 rounded-3xl border-2 border-dashed border-red-100 bg-red-50/40 flex flex-col items-center">
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-3 block tracking-widest" for="slide-main-file">{{ t('pages.web_landing_editor.mainImage') }}</label>
                            <img v-if="mainImagePreview" :src="mainImagePreview" :alt="t('pages.web_landing_editor.mainPreviewAlt')" class="w-full h-32 object-cover rounded-xl mb-3 border-4 border-white shadow-xl" />
                            <input id="slide-main-file" type="file" accept="image/jpeg,image/png,image/webp,image/avif" @change="handleMainPick" class="text-xs font-bold text-slate-500" />
                            <InputText v-model="slideForm.main_image" class="w-full !rounded-xl !p-2 !mt-3 !text-xs !bg-white" :placeholder="t('pages.web_landing_editor.mainImageUrlPlaceholder')" />
                        </div>

                        <fieldset class="p-4 rounded-3xl border border-slate-200 bg-white">
                            <legend class="text-[10px] font-black uppercase text-slate-400 px-2 tracking-widest">{{ t('pages.web_landing_editor.floatingCard') }}</legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="slide-card-title">{{ t('pages.web_landing_editor.fieldCardTitle') }}</label><InputText id="slide-card-title" v-model="slideForm.card_title" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.cardTitlePlaceholder')" /></div>
                                <div class="col-span-2"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="slide-card-sub">{{ t('pages.web_landing_editor.fieldCardSub') }}</label><InputText id="slide-card-sub" v-model="slideForm.card_sub" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.cardSubPlaceholder')" /></div>
                                <div class="col-span-2"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="slide-icon">{{ t('pages.web_landing_editor.fieldIcon') }}</label><InputText id="slide-icon" v-model="slideForm.icon" class="w-full !rounded-xl" placeholder="pi-globe" /></div>
                            </div>
                        </fieldset>
                    </div>
                </form>
                <template #footer>
                    <div class="flex justify-end gap-3 p-4 border-t border-slate-50">
                        <Button :label="t('pages.web_landing_editor.cancel')" text @click="showSlideDialog = false" class="!text-slate-400 !font-bold" />
                        <Button :label="editingSlide ? t('pages.web_landing_editor.update') : t('pages.web_landing_editor.save')" icon="pi pi-check" :loading="slideForm.processing" @click="saveSlide" class="!bg-red-600 !border-none !rounded-xl !px-10" />
                    </div>
                </template>
            </Dialog>

            <!-- DIALOGS TECH & STATS (Simplified) -->
            <Dialog v-model:visible="showTechDialog" modal :header="t('pages.web_landing_editor.techDialogTitle')" :style="{ width: 'min(560px, 96vw)' }" class="!rounded-[1rem]">
                 <form @submit.prevent="saveTech" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    <div v-if="Object.keys(techForm.errors).length" class="bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl px-4 py-3" role="alert">
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(message, field) in techForm.errors" :key="field">{{ message }}</li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-name">{{ t('pages.web_landing_editor.fieldName') }}</label><InputText id="tech-name" v-model="techForm.name" class="w-full !rounded-xl" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-color">{{ t('pages.web_landing_editor.fieldColor') }}</label><InputText id="tech-color" v-model="techForm.color" type="color" class="w-full !h-12 !p-1 !rounded-xl" /></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-slug">Slug</label><InputText id="tech-slug" v-model="techForm.slug" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.slugPlaceholder')" /></div>
                        <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-category">{{ t('pages.web_landing_editor.fieldCategory') }}</label><InputText id="tech-category" v-model="techForm.category" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.categoryPlaceholder')" /></div>
                    </div>
                    <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-icon">{{ t('pages.web_landing_editor.fieldIcon') }}</label><InputText id="tech-icon" v-model="techForm.icon" class="w-full !rounded-xl" placeholder="🚀 ou pi-database" /></div>
                    <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-description">{{ t('pages.web_landing_editor.fieldDescription') }}</label><Textarea id="tech-description" v-model="techForm.description" rows="3" class="w-full !rounded-xl !p-3 !bg-slate-50" :placeholder="t('pages.web_landing_editor.techDescPlaceholder')" /></div>
                    <div><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="tech-position">{{ t('pages.web_landing_editor.position') }}</label><input id="tech-position" type="number" min="1" v-model.number="techForm.position" class="w-28 !rounded-xl !p-3 border border-slate-200 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-500" /></div>
                    <Button :label="t('pages.web_landing_editor.save')" :loading="techForm.processing" @click="saveTech" class="w-full !bg-red-600 !rounded-xl !py-4" />
                 </form>
            </Dialog>

            <Dialog v-model:visible="showStatDialog" modal :header="t('pages.web_landing_editor.statDialogTitle')" :style="{ width: 'min(480px, 96vw)' }" class="!rounded-[1rem]">
                <form @submit.prevent="saveStat" class="p-6 space-y-5 text-center">
                    <div v-if="Object.keys(statForm.errors).length" class="bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl px-4 py-3 text-left" role="alert">
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(message, field) in statForm.errors" :key="field">{{ message }}</li>
                        </ul>
                    </div>
                    <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-2 text-2xl border border-slate-100" :style="{ color: statForm.color }">
                        <i :class="`pi ${statForm.icon}`"></i>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-left"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="stat-value">{{ t('pages.web_landing_editor.fieldValue') }}</label><InputText id="stat-value" v-model="statForm.value" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.valuePlaceholder')" /></div>
                        <div class="text-left"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="stat-color">{{ t('pages.web_landing_editor.fieldColor') }}</label><InputText id="stat-color" v-model="statForm.color" type="color" class="w-full !h-[42px] !p-1 !rounded-xl" /></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-left"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="stat-icon">{{ t('pages.web_landing_editor.fieldIcon') }}</label><InputText id="stat-icon" v-model="statForm.icon" class="w-full !rounded-xl" placeholder="pi-chart-bar" /></div>
                        <div class="text-left"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="stat-position">{{ t('pages.web_landing_editor.position') }}</label><input id="stat-position" type="number" min="1" v-model.number="statForm.position" class="w-full !rounded-xl !p-3 border border-slate-200 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-500" /></div>
                    </div>
                    <div class="text-left"><label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest" for="stat-label">{{ t('pages.web_landing_editor.fieldLabel') }}</label><InputText id="stat-label" v-model="statForm.label" class="w-full !rounded-xl" :placeholder="t('pages.web_landing_editor.labelPlaceholder')" /></div>
                    <p class="text-[10px] text-slate-400 italic -mt-2">{{ t('pages.web_landing_editor.statNote') }}</p>
                    <Button :label="t('pages.web_landing_editor.confirm')" :loading="statForm.processing" @click="saveStat" class="w-full !bg-red-600 !rounded-xl !py-4" />
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