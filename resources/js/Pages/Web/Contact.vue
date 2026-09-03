<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    legal: { type: Object, default: () => null },
});

const form = useForm({
    name: '', email: '', phone: '', subject: '', message: '',
});

const submit = () => form.post(route('contact.store'), { onSuccess: () => form.reset() });

const flash = computed(() => usePage().props.flash || {});
import { computed } from 'vue';
</script>

<template>
    <Head :title="`Contact - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">Contact</h1>
                <p class="text-xl text-blue-100 mt-4">Nous sommes à votre écoute</p>
            </div>
        </section>

        <section class="py-20 px-6 bg-white">
            <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16">

                <!-- COORDONNÉES -->
                <div>
                    <h2 class="text-3xl font-black text-slate-900 mb-6">Nos coordonnées</h2>

                    <div class="space-y-5 text-slate-700">
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                                <i class="pi pi-map-marker text-xl"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Adresse</p>
                                <p class="font-bold text-slate-900">{{ legal?.address_line || identity.contact_address || '—' }}</p>
                                <p v-if="legal?.city || legal?.province || legal?.country" class="text-sm text-slate-600">
                                    {{ [legal?.city, legal?.province, legal?.country].filter(Boolean).join(', ') }}
                                </p>
                                <p v-if="legal?.po_box" class="text-sm text-slate-500">BP : {{ legal.po_box }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                                <i class="pi pi-envelope text-xl"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Email</p>
                                <a :href="`mailto:${identity.contact_email}`" class="font-bold text-slate-900 hover:underline">{{ identity.contact_email }}</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                                <i class="pi pi-phone text-xl"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Téléphone</p>
                                <a :href="`tel:${identity.contact_phone}`" class="font-bold text-slate-900 hover:underline">{{ identity.contact_phone }}</a>
                            </div>
                        </div>

                        <div v-if="identity.social_facebook !== '#' || identity.social_twitter !== '#' || identity.social_linkedin !== '#' || identity.social_youtube !== '#'" class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                                <i class="pi pi-share-alt text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Réseaux sociaux</p>
                                <div class="flex gap-3 mt-2">
                                    <a v-if="identity.social_facebook && identity.social_facebook !== '#'" :href="identity.social_facebook" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white shadow hover:scale-110 hover:bg-blue-600 hover:text-white flex items-center justify-center transition"><i class="pi pi-facebook"></i></a>
                                    <a v-if="identity.social_twitter && identity.social_twitter !== '#'"  :href="identity.social_twitter"  target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white shadow hover:scale-110 hover:bg-sky-500 hover:text-white flex items-center justify-center transition"><i class="pi pi-twitter"></i></a>
                                    <a v-if="identity.social_linkedin && identity.social_linkedin !== '#'" :href="identity.social_linkedin" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white shadow hover:scale-110 hover:bg-blue-700 hover:text-white flex items-center justify-center transition"><i class="pi pi-linkedin"></i></a>
                                    <a v-if="identity.social_youtube && identity.social_youtube !== '#'"  :href="identity.social_youtube"  target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white shadow hover:scale-110 hover:bg-red-600 hover:text-white flex items-center justify-center transition"><i class="pi pi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INFORMATIONS LÉGALES (RCCM/IFU) -->
                    <div v-if="legal && (legal.company_name || legal.rccm || legal.ifu || legal.tax_id)" class="mt-8 p-6 bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl shadow-lg">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center">
                                <i class="pi pi-id-card text-xl"></i>
                            </div>
                            <h3 class="text-lg font-black">Informations légales</h3>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <template v-if="legal.company_name">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Raison sociale</dt>
                                <dd class="font-bold mb-2">{{ legal.company_name }}<span v-if="legal.legal_form" class="text-slate-400"> {{ legal.legal_form }}</span></dd>
                            </template>
                            <template v-if="legal.rccm">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">RCCM</dt>
                                <dd class="font-mono mb-2">{{ legal.rccm }}</dd>
                            </template>
                            <template v-if="legal.ifu">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">IFU</dt>
                                <dd class="font-mono mb-2">{{ legal.ifu }}</dd>
                            </template>
                            <template v-if="legal.tax_id">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">N° Contribuable</dt>
                                <dd class="font-mono mb-2">{{ legal.tax_id }}</dd>
                            </template>
                            <template v-if="legal.nif">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">NIF</dt>
                                <dd class="font-mono mb-2">{{ legal.nif }}</dd>
                            </template>
                            <template v-if="legal.ai">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Article d'Imposition</dt>
                                <dd class="font-mono mb-2">{{ legal.ai }}</dd>
                            </template>
                            <template v-if="legal.capital_social">
                                <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Capital social</dt>
                                <dd class="font-bold mb-2">{{ legal.capital_social }}</dd>
                            </template>
                        </dl>
                    </div>
                </div>

                <!-- FORMULAIRE -->
                <form @submit.prevent="submit" class="space-y-4 bg-slate-50 p-8 rounded-2xl shadow-sm border border-slate-100 self-start">
                    <h2 class="text-2xl font-black text-slate-900 mb-2">Envoyez-nous un message</h2>
                    <p v-if="flash.success" class="p-4 bg-emerald-100 text-emerald-800 rounded-xl text-sm font-bold">
                        <i class="pi pi-check-circle mr-2"></i>{{ flash.success }}
                    </p>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Nom complet *</label>
                        <input v-model="form.name" type="text" placeholder="Jean-Paul Mulumba" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none" required />
                        <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Email *</label>
                            <input v-model="form.email" type="email" placeholder="exemple@email.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none" required />
                            <p v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Téléphone</label>
                            <input v-model="form.phone" type="tel" placeholder="+243 000 000 000" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Sujet *</label>
                        <input v-model="form.subject" type="text" placeholder="Ex : Demande de partenariat" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none" required />
                        <p v-if="form.errors.subject" class="text-rose-500 text-xs mt-1">{{ form.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Message *</label>
                        <textarea v-model="form.message" placeholder="Votre message..." rows="5" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none" required></textarea>
                        <p v-if="form.errors.message" class="text-rose-500 text-xs mt-1">{{ form.errors.message }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing" class="w-full px-6 py-4 bg-[#003399] text-white font-black rounded-xl hover:bg-blue-800 transition disabled:opacity-50">
                        <i class="pi pi-send mr-2"></i>{{ form.processing ? 'Envoi...' : 'Envoyer le message' }}
                    </button>
                </form>
            </div>
        </section>
    </AppLayout>
</template>
