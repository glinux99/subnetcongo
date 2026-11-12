<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    legal_content: { type: Object, default: () => ({}) },
});

const page = usePage();

// `legal` est partagé globalement par HandleInertiaRequests (LegalInfo::first()).
const legal = computed(() => page.props.legal || null);
const identity = computed(() => props.identity || {});

const missing = t('public.mentions.missing');

const companyName = computed(() => legal.value?.company_name || identity.value?.site_name || 'SubnetCongo');

const capitalDisplay = computed(() => {
    const raw = legal.value?.capital_social;
    if (!raw) return null;
    const trimmed = String(raw).trim();
    return /[A-Za-z]/.test(trimmed) ? trimmed : `${trimmed} ${legal.value?.currency || 'CDF'}`;
});

const legalFormCapital = computed(() => {
    const parts = [];
    if (legal.value?.legal_form) parts.push(legal.value.legal_form);
    if (capitalDisplay.value) parts.push(`${t('public.mentions.capital_join')} ${capitalDisplay.value}`);
    return parts.length ? parts.join(' ') : null;
});

const seatAddress = computed(() => {
    const l = legal.value;
    const parts = [l?.address_line, l?.city, l?.province, l?.country].filter(Boolean);
    if (parts.length) return parts.join(', ');
    return identity.value?.contact_address || t('public.mentions.seat_fallback');
});

const legalFilled = computed(() => {
    const l = legal.value;
    if (!l) return false;
    return ['company_name', 'legal_form', 'rccm', 'ifu', 'tax_id', 'nif', 'vat_number', 'capital_social', 'address_line', 'po_box', 'website'].some((k) => l[k]);
});

const legalUpdated = computed(() => {
    const iso = legal.value?.updated_at;
    if (!iso) return null;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return null;
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
});

const websiteHref = computed(() => {
    const w = legal.value?.website;
    if (!w) return null;
    return /^https?:\/\//i.test(w) ? w : `https://${w}`;
});
</script>

<template>
    <Head :title="`${t('public.mentions.head_title')} - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        
        <!-- En-tête de la page -->
        <section class="relative pt-32 pb-32 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] via-[#003399]/80 to-[#001A4D] opacity-90"></div>
            
            <!-- Motif de fond discret (Optionnel pour un look tech) -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white via-transparent to-transparent bg-[length:20px_20px]"></div>

            <div class="relative max-w-5xl mx-auto px-6 text-center z-10">
                <span class="inline-flex items-center gap-2 text-red-400 mb-4">
                    <span class="w-8 h-[2px] bg-red-400"></span>
                    <span class="font-bold uppercase tracking-[0.3em] text-xs">{{ t('public.mentions.pill') }}</span>
                    <span class="w-8 h-[2px] bg-red-400"></span>
                </span>
                <h1 class="text-4xl md:text-6xl font-black mt-2 tracking-tight">{{ t('public.mentions.hero_title') }}</h1>
                <p class="text-lg text-blue-200 mt-6 max-w-3xl mx-auto font-medium">
                    {{ t('public.mentions.hero_sub') }}
                </p>
            </div>
        </section>

        <!-- Contenu Légal (Style Document) -->
        <section class="pb-20 bg-slate-50 px-4 sm:px-6 relative">
            
            <!-- Le bloc principal remonte sur le header bleu avec une ombre -->
            <div class="max-w-8xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-100 p-8 sm:p-12 -mt-16 relative z-20 space-y-16">

                <!-- 1. Éditeur -->
                <article>
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">01</span>
                        {{ t('public.mentions.editor_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed mb-6">
                        {{ t('public.mentions.edit_lead') }} <strong>{{ identity.site_name || 'SubnetCongo' }}</strong> {{ t('public.mentions.edit_lead_2') }}
                    </div>

                    <!-- Fiche entreprise (Grille professionnelle) -->
                    <div v-if="legalFilled" class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 bg-slate-50 p-6 sm:p-8 rounded-xl border border-slate-200 text-sm">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_company') }}</span>
                            <span class="font-semibold text-slate-900 text-base">{{ legal?.company_name || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_form_capital') }}</span>
                            <span class="font-medium text-slate-700">{{ legalFormCapital || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_rccm') }}</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.rccm || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_ifu') }}</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.ifu || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_tax') }}</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.tax_id || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_nif') }}</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.nif || missing }}</span>
                        </div>
                        <div v-if="legal?.vat_number">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_vat') }}</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal.vat_number }}</span>
                        </div>
                        <div v-if="legal?.po_box">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_pobox') }}</span>
                            <span class="font-medium text-slate-700">{{ legal.po_box }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_seat') }}</span>
                            <span class="font-medium text-slate-700">{{ seatAddress }}</span>
                        </div>
                        <div v-if="websiteHref">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_website') }}</span>
                            <a :href="websiteHref" target="_blank" rel="noopener" class="font-medium text-[#003399] hover:underline break-all">{{ legal.website }}</a>
                        </div>
                        <div class="md:col-span-2 pt-4 border-t border-slate-200 mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_contact') }}</span>
                                <span class="font-medium text-[#003399]">{{ identity.contact_phone || t('public.mentions.contact_fallback') }}</span><br>
                                <span class="font-medium text-[#003399]">{{ identity.contact_email || t('public.mentions.contact_fallback') }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('public.mentions.label_director') }}</span>
                                <span class="font-medium text-slate-700">{{ t('public.mentions.director_fallback') }} {{ companyName }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fiche non renseignée : repli élégant -->
                    <div v-else class="border border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50">
                        <i class="pi pi-info-circle text-3xl text-slate-300 mb-3" aria-hidden="true"></i>
                        <p class="font-semibold text-slate-700">{{ t('public.mentions.not_filled_title') }}</p>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">{{ t('public.mentions.not_filled_desc') }}</p>
                        <p class="text-sm text-[#003399] mt-4">{{ identity.contact_email || 'contact@subnetcongo.cd' }} · {{ identity.contact_phone || '' }}</p>
                    </div>
                </article>

                <!-- 2. Hébergement -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">02</span>
                        {{ t('public.mentions.host_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4">
                        <p>{{ t('public.mentions.host_desc') }}</p>
                        <div class="bg-blue-50/50 p-4 border border-blue-100 rounded-lg">
                            <ul class="space-y-2 text-sm">
                                <li><strong class="text-slate-800">{{ t('public.mentions.host_label_provider') }}</strong> {{ companyName }} {{ t('public.mentions.host_provider_suffix') }}</li>
                                <li><strong class="text-slate-800">{{ t('public.mentions.host_label_loc') }}</strong> {{ seatAddress }}</li>
                                <li><strong class="text-slate-800">{{ t('public.mentions.host_label_supervision') }}</strong> {{ t('public.mentions.host_supervision_text') }}</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 3. Propriété Intellectuelle -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">03</span>
                        {{ t('public.mentions.ip_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ t('public.mentions.ip_p1_1') }} <strong>{{ identity.site_name || 'SubnetCongo' }}</strong> {{ t('public.mentions.ip_p1_2') }} {{ companyName }}.</p>
                        <p>{{ t('public.mentions.ip_p2') }}</p>
                        <div class="border-l-4 border-red-500 pl-4 bg-red-50/50 p-4 rounded-r-lg mt-4 text-sm text-slate-700">
                            <strong>{{ t('public.mentions.legal_warning_title') }}</strong> {{ t('public.mentions.legal_warning') }} {{ companyName }}{{ t('public.mentions.legal_warning_2') }}
                        </div>
                    </div>
                </article>

                <!-- 4. Données Personnelles -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">04</span>
                        {{ t('public.mentions.data_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ t('public.mentions.data_p1_1') }} <strong>Loi n° 20/017 du 25 novembre 2020</strong> {{ t('public.mentions.data_p1_2') }}</p>
                        <p>{{ t('public.mentions.data_p2') }}</p>
                        <p>{{ t('public.mentions.data_p3_1') }} <a :href="`mailto:${identity.contact_email || 'contact@subnetcongo.cd'}`" class="text-[#003399] font-semibold hover:underline">{{ identity.contact_email || 'contact@subnetcongo.cd' }}</a>.</p>
                        <p class="text-sm bg-slate-100 p-3 rounded">
                            {{ t('public.mentions.data_more') }} 
                            <Link :href="route('confidentialite')" class="text-[#E11D48] font-bold hover:underline">{{ t('public.mentions.data_privacy') }}</Link>.
                        </p>
                    </div>
                </article>

                <!-- 5. Cookies (NOUVEAU) -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">05</span>
                        {{ t('public.mentions.cookies_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ t('public.mentions.cookies_p1') }}</p>
                        <p>{{ t('public.mentions.cookies_p2') }}</p>
                    </div>
                </article>

                <!-- 6. Sécurité (NOUVEAU) -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">06</span>
                        {{ t('public.mentions.security_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ companyName }} {{ t('public.mentions.security_p1_1') }}</p>
                        <p>{{ t('public.mentions.security_p2') }} <strong>SSL/TLS (HTTPS)</strong>.</p>
                    </div>
                </article>

                <!-- 7. Responsabilité -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">07</span>
                        {{ t('public.mentions.liability_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ t('public.mentions.liability_p1_1') }} {{ companyName }} {{ t('public.mentions.liability_p1_2') }}</p>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>{{ t('public.mentions.liability_li1') }}</li>
                            <li>{{ t('public.mentions.liability_li2') }}</li>
                            <li>{{ t('public.mentions.liability_li3') }}</li>
                        </ul>
                        <p>{{ t('public.mentions.liability_p2') }}</p>
                    </div>
                </article>

                <!-- 8. Juridiction -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">08</span>
                        {{ t('public.mentions.law_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ t('public.mentions.law_p1_1') }} <strong>{{ t('public.mentions.law_p1_2') }}</strong>.</p>
                        <p>{{ t('public.mentions.law_p2_1') }} <strong>{{ t('public.mentions.law_p2_2') }}</strong>.</p>
                    </div>
                </article>

            </div>
            
            <!-- Date de mise à jour -->
            <div class="max-w-4xl mx-auto mt-8 text-center text-sm text-slate-400">
                <p v-if="legalUpdated">{{ t('public.mentions.update_date') }} <span class="font-semibold text-slate-500">{{ legalUpdated }}</span><br></p>
                {{ companyName }} {{ t('public.mentions.update_reserve') }}
            </div>

        </section>
    </AppLayout>
</template>