<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    privacy_content: { type: Object, default: () => ({}) },
});

const page = usePage();

// `legal` est partagé globalement par HandleInertiaRequests (LegalInfo::first()).
const legal = computed(() => page.props.legal || null);
const identity = computed(() => props.identity || {});

const missing = t('public.conf.missing');

const companyName = computed(() => legal.value?.company_name || identity.value?.site_name || 'SubnetCongo');

const seatAddress = computed(() => {
    const l = legal.value;
    const parts = [l?.address_line, l?.city, l?.province, l?.country].filter(Boolean);
    if (parts.length) return parts.join(', ');
    return identity.value?.contact_address || t('public.conf.seat_fallback');
});

const legalFilled = computed(() => {
    const l = legal.value;
    if (!l) return false;
    return ['company_name', 'rccm', 'ifu', 'nif', 'capital_social', 'address_line'].some((k) => l[k]);
});

const legalUpdated = computed(() => {
    const iso = legal.value?.updated_at;
    if (!iso) return null;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return null;
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
});
</script>

<template>
    <Head :title="`${t('public.conf.head_title')} - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        
        <!-- En-tête de la page -->
        <section class="relative pt-32 pb-32 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] via-[#003399]/80 to-[#001A4D] opacity-90"></div>
            
            <!-- Motif de fond discret -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white via-transparent to-transparent bg-[length:20px_20px]"></div>

            <div class="relative max-w-5xl mx-auto px-6 text-center z-10">
                <span class="inline-flex items-center gap-2 text-red-400 mb-4">
                    <span class="w-8 h-[2px] bg-red-400"></span>
                    <span class="font-bold uppercase tracking-[0.3em] text-xs">{{ t('public.conf.pill') }}</span>
                    <span class="w-8 h-[2px] bg-red-400"></span>
                </span>
                <h1 class="text-4xl md:text-6xl font-black mt-2 tracking-tight">{{ t('public.conf.hero_title') }}</h1>
                <p class="text-lg text-blue-200 mt-6 max-w-3xl mx-auto font-medium">
                    {{ t('public.conf.hero_sub_1') }} {{ identity.site_name || 'SubnetCongo' }} {{ t('public.conf.hero_sub_2') }}
                </p>
            </div>
        </section>

        <!-- Contenu Légal (Style Document) -->
        <section class="pb-20 bg-slate-50 px-4 sm:px-6 relative">
            
            <!-- Le bloc principal remonte sur le header bleu avec une ombre -->
            <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-100 p-8 sm:p-12 -mt-16 relative z-20 space-y-16">

                <!-- 1. Qui sommes-nous ? -->
                <article>
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">01</span>
                        {{ t('public.conf.identity_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p><strong>{{ companyName }}</strong> — {{ t('public.conf.edited_by') }}</p>
                        <p v-if="legalFilled">{{ t('public.conf.published_by') }} <strong>{{ legal?.company_name || missing }}</strong><template v-if="legal?.legal_form">, {{ legal.legal_form }}</template><template v-if="legal?.rccm">{{ t('public.conf.registered_rccm') }} <strong class="font-mono">{{ legal.rccm }}</strong></template><template v-if="legal?.ifu">, IFU <strong class="font-mono">{{ legal.ifu }}</strong></template>{{ t('public.conf.registered_seat') }} {{ seatAddress }}.</p>
                        <p v-else class="text-slate-500 italic">{{ t('public.conf.not_filled') }}</p>
                        <p>{{ t('public.conf.data_commitment') }} <strong>Loi n° 20/017 du 25 novembre 2020</strong> {{ t('public.conf.data_commitment_end') }}</p>
                    </div>
                </article>

                <!-- 2. Données collectées -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">02</span>
                        {{ t('public.conf.collected_title') }}
                    </h2>
                    <p class="text-slate-600 mb-6">{{ t('public.conf.collected_intro') }}</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">{{ t('public.conf.data_id_title') }}</h4>
                            <p class="text-sm text-slate-600">{{ t('public.conf.data_id_desc') }}</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">{{ t('public.conf.data_nav_title') }}</h4>
                            <p class="text-sm text-slate-600">{{ t('public.conf.data_nav_desc') }}</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">{{ t('public.conf.data_peda_title') }}</h4>
                            <p class="text-sm text-slate-600">{{ t('public.conf.data_peda_desc') }}</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">{{ t('public.conf.data_payment_title') }}</h4>
                            <p class="text-sm text-slate-600">{{ t('public.conf.data_payment_desc') }}</p>
                        </div>
                    </div>
                </article>

                <!-- 3. Finalités -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">03</span>
                        {{ t('public.conf.why_title') }}
                    </h2>
                    <ul class="space-y-4 text-slate-600">
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">{{ t('public.conf.purpose_1') }}</strong> {{ t('public.conf.purpose_1_desc') }}</div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">{{ t('public.conf.purpose_2') }}</strong> {{ t('public.conf.purpose_2_desc') }}</div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">{{ t('public.conf.purpose_3') }}</strong> {{ t('public.conf.purpose_3_desc') }}</div>
                        </li>
                    </ul>
                </article>

                <!-- 4. Conservation -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">04</span>
                        {{ t('public.conf.retention_title') }}
                    </h2>
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden text-sm">
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">{{ t('public.conf.ret_account_title') }}</span>
                            <span class="text-slate-500">{{ t('public.conf.ret_account_desc') }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">{{ t('public.conf.ret_billing_title') }}</span>
                            <span class="text-slate-500">{{ t('public.conf.ret_billing_desc') }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">{{ t('public.conf.ret_cookie_title') }}</span>
                            <span class="text-slate-500">{{ t('public.conf.ret_cookie_desc') }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">{{ t('public.conf.ret_logs_title') }}</span>
                            <span class="text-slate-500">{{ t('public.conf.ret_logs_desc') }}</span>
                        </div>
                    </div>
                </article>

                <!-- 5. Partage -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">05</span>
                        {{ t('public.conf.share_title') }}
                    </h2>
                    <div class="text-slate-600 leading-relaxed text-justify">
                        <p>{{ t('public.conf.share_p1_1') }} <strong>{{ t('public.conf.share_p1_2') }}</strong>{{ t('public.conf.share_p1_3') }}</p>
                        <p class="mt-4">{{ t('public.conf.share_p2') }}</p>
                    </div>
                </article>

                <!-- 6. Vos droits -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">06</span>
                        {{ t('public.conf.rights_title') }}
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">{{ t('public.conf.right_access_title') }}</h4>
                            <p class="text-xs text-slate-500">{{ t('public.conf.right_access_desc') }}</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">{{ t('public.conf.right_rectify_title') }}</h4>
                            <p class="text-xs text-slate-500">{{ t('public.conf.right_rectify_desc') }}</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">{{ t('public.conf.right_delete_title') }}</h4>
                            <p class="text-xs text-slate-500">{{ t('public.conf.right_delete_desc') }}</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm">
                        {{ t('public.conf.rights_footer_1') }} <a :href="`mailto:${identity.contact_email || 'contact@subnetcongo.cd'}`" class="text-[#003399] font-bold hover:underline">{{ identity.contact_email || 'contact@subnetcongo.cd' }}</a>. {{ t('public.conf.rights_footer_2') }}
                    </p>
                </article>

                <!-- 7 & 8. Sécurité et Cookies -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">07</span>
                        {{ t('public.conf.security_cookies_title') }}
                    </h2>
                    <div class="flex flex-col md:flex-row gap-8">
                        <div class="flex-1 text-slate-600 text-justify">
                            <h4 class="font-bold text-slate-900 mb-2">{{ t('public.conf.security_infra_title') }}</h4>
                            <p class="text-sm mb-4">{{ t('public.conf.security_infra_desc') }}</p>
                        </div>
                        <div class="flex-1 text-slate-600 text-justify">
                            <h4 class="font-bold text-slate-900 mb-2">{{ t('public.conf.trackers_title') }}</h4>
                            <p class="text-sm">{{ t('public.conf.trackers_desc') }}</p>
                        </div>
                    </div>
                </article>
                
            </div>

            <!-- Footer Document Légal -->
            <div class="max-w-4xl mx-auto mt-8 text-center text-sm text-slate-500 space-y-2">
                <p>
                    {{ t('public.conf.footer_legal') }} 
                    <Link :href="route('mentions-legales')" class="text-[#E11D48] font-bold hover:underline">{{ t('public.conf.footer_mentions') }}</Link>.
                </p>
                <p v-if="legalUpdated">
                    {{ t('public.conf.footer_update') }} <span class="font-semibold text-slate-600">{{ legalUpdated }}</span><br>
                </p>
                <p>
                    {{ companyName }} {{ t('public.conf.footer_reserve') }}
                </p>
            </div>

        </section>
    </AppLayout>
</template>