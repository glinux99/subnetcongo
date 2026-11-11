<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageCard from '@/Components/PageCard.vue';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';

const props = defineProps({
    legal: { type: Object, default: () => ({}) },
});

const toast = useToast();

const lastSavedAt = computed(() => props.legal?.updated_at || null);
const isSaved = computed(() => !!lastSavedAt.value);

const formatDate = (iso) => {
    if (!iso) return null;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return null;
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    }).format(d);
};

const statusLabel = computed(() =>
    isSaved.value ? `Enregistré le ${formatDate(lastSavedAt.value)}` : 'Brouillon — non enregistré'
);

const form = useForm({
    company_name:   props.legal.company_name   || '',
    legal_form:     props.legal.legal_form     || '',
    rccm:           props.legal.rccm           || '',
    ifu:            props.legal.ifu            || '',
    tax_id:         props.legal.tax_id         || '',
    nif:            props.legal.nif            || '',
    ai:             props.legal.ai             || '',
    vat_number:     props.legal.vat_number     || '',
    tva_rate:       props.legal.tva_rate !== null && props.legal.tva_rate !== undefined ? Number(props.legal.tva_rate) : 16,
    currency:       props.legal.currency       || 'CDF',
    country:        props.legal.country        || 'République Démocratique du Congo',
    province:       props.legal.province       || '',
    city:           props.legal.city           || '',
    address_line:   props.legal.address_line   || '',
    po_box:         props.legal.po_box         || '',
    website:        props.legal.website        || '',
    capital_social: props.legal.capital_social || '',
    logo_url:       props.legal.logo_url       || '',
    logo:           null,
});

const logoPreview = ref(props.legal.logo_url || null);

const onLogoSelect = (event) => {
    const file = event.files?.[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const clearLogo = () => {
    form.logo = null;
    form.logo_url = '';
    logoPreview.value = null;
};

const submit = () => {
    form.transform((data) => {
        const payload = { ...data };
        if (!payload.logo) delete payload.logo;
        payload.tva_rate = payload.tva_rate === '' || payload.tva_rate === null || payload.tva_rate === undefined
            ? null
            : Number(payload.tva_rate);
        return payload;
    }).post(route('admin.legal.save'), {
        preserveScroll: true,
        forceFormData: true,
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs du formulaire.', life: 4000 });
        },
    });
};

const resetForm = () => {
    form.reset();
    logoPreview.value = props.legal.logo_url || null;
};

const currencies = [
    { value: 'CDF' },
    { value: 'USD' },
    { value: 'EUR' },
];

const labelClass = 'block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5';
const hintClass = 'text-xs text-slate-400 mt-1';
const errorClass = 'text-xs text-rose-600 dark:text-rose-400 mt-1';
const inputClass = 'w-full !rounded-xl';
</script>

<template>
    <AppLayout>
        <Head title="Identité légale | SubnetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Identité légale"
                subtitle="Gérez les informations officielles de votre société. Ces données sont utilisées sur les factures, contrats, certificats et pages publiques (mentions légales, contact)."
                icon="pi-id-card"
                :accent="isSaved ? 'emerald' : 'amber'"
                :badge="statusLabel"
                :actions="[{ label: 'Enregistrer', icon: 'pi-check', variant: 'primary', onClick: submit, disabled: form.processing }]"
            />

            <div class="page-container">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- SECTION : Structure de la société -->
                    <PageCard title="Structure de la société" subtitle="Dénomination, forme sociale et capital.">
                        <template #header>
                            <i class="pi pi-building text-slate-400" aria-hidden="true"></i>
                        </template>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label for="company_name" :class="labelClass">Raison sociale <span class="text-rose-600" aria-hidden="true">*</span></label>
                                <InputText id="company_name" v-model="form.company_name" required maxlength="255"
                                       class="w-full" :invalid="!!form.errors.company_name" placeholder="Ex. SUBNETCONGO SARL" />
                                <p v-if="form.errors.company_name" :class="errorClass" role="alert">{{ form.errors.company_name }}</p>
                            </div>
                            <div>
                                <label for="legal_form" :class="labelClass">Forme juridique</label>
                                <InputText id="legal_form" v-model="form.legal_form" maxlength="100" class="w-full" placeholder="SARL, SA, SAS…" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="capital_social" :class="labelClass">Capital social</label>
                                    <InputText id="capital_social" v-model="form.capital_social" maxlength="100" class="w-full" placeholder="Ex. 10 000 000" />
                                </div>
                                <div>
                                    <label for="currency" :class="labelClass">Devise par défaut</label>
                                    <Select id="currency" v-model="form.currency" :options="currencies"
                                            optionLabel="value" optionValue="value" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </PageCard>

                    <!-- SECTION : Fiscalité & identifiants -->
                    <PageCard title="Fiscalité & identifiants" subtitle="Numéros d'enregistrement auprès des autorités (RCCM, IFU, NIF, TVA…).">
                        <template #header>
                            <i class="pi pi-percentage text-slate-400" aria-hidden="true"></i>
                        </template>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="rccm" :class="labelClass">RCCM</label>
                                <InputText id="rccm" v-model="form.rccm" maxlength="100" class="w-full font-mono" placeholder="CD/KIN/RCCM/25-B-…" />
                            </div>
                            <div>
                                <label for="ifu" :class="labelClass">IFU (Identifiant fiscal unique)</label>
                                <InputText id="ifu" v-model="form.ifu" maxlength="50" class="w-full font-mono" placeholder="A00000000X" />
                            </div>
                            <div>
                                <label for="tax_id" :class="labelClass">N° impôt (tax ID)</label>
                                <InputText id="tax_id" v-model="form.tax_id" maxlength="100" class="w-full font-mono" placeholder="Ex. A123456789B" />
                            </div>
                            <div>
                                <label for="nif" :class="labelClass">NIF</label>
                                <InputText id="nif" v-model="form.nif" maxlength="50" class="w-full font-mono" placeholder="Ex. 01-9-N00000A" />
                            </div>
                            <div>
                                <label for="ai" :class="labelClass">Article d’imposition (AI)</label>
                                <InputText id="ai" v-model="form.ai" maxlength="50" class="w-full font-mono" />
                            </div>
                            <div>
                                <label for="vat_number" :class="labelClass">N° TVA</label>
                                <InputText id="vat_number" v-model="form.vat_number" maxlength="50" class="w-full font-mono" placeholder="Ex. 0000000/A" />
                                <p :class="hintClass">Numéro d'identification à la TVA, si attribué.</p>
                            </div>
                            <div>
                                <label for="tva_rate" :class="labelClass">Taux TVA (%)</label>
                                <InputNumber id="tva_rate" v-model="form.tva_rate" :min="0" :max="100" :step="0.01"
                                             :useGrouping="false" class="w-full" :invalid="!!form.errors.tva_rate" />
                                <p v-if="form.errors.tva_rate" :class="errorClass" role="alert">{{ form.errors.tva_rate }}</p>
                                <p v-else :class="hintClass">16 % par défaut en RDC.</p>
                            </div>
                        </div>
                    </PageCard>

                    <!-- SECTION : Siège social & coordonnées -->
                    <PageCard title="Siège social & coordonnées" subtitle="Adresse physique, boîte postale et présence en ligne.">
                        <template #header>
                            <i class="pi pi-map-marker text-slate-400" aria-hidden="true"></i>
                        </template>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label for="country" :class="labelClass">Pays</label>
                                <InputText id="country" v-model="form.country" maxlength="100" class="w-full" />
                            </div>
                            <div>
                                <label for="province" :class="labelClass">Province</label>
                                <InputText id="province" v-model="form.province" maxlength="100" class="w-full" placeholder="Ex. Kinshasa" />
                            </div>
                            <div>
                                <label for="city" :class="labelClass">Ville</label>
                                <InputText id="city" v-model="form.city" maxlength="100" class="w-full" placeholder="Ex. Kinshasa, Goma…" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="address_line" :class="labelClass">Adresse (avenue, quartier, commune)</label>
                                <InputText id="address_line" v-model="form.address_line" maxlength="500" class="w-full" placeholder="Ex. Av. de l'OUA, n° 15, Quartier …, Commune …" />
                            </div>
                            <div>
                                <label for="po_box" :class="labelClass">Boîte postale</label>
                                <InputText id="po_box" v-model="form.po_box" maxlength="50" class="w-full" placeholder="Ex. BP 1234 Kinshasa" />
                            </div>
                            <div class="md:col-span-3">
                                <label for="website" :class="labelClass">Site web</label>
                                <InputText id="website" v-model="form.website" maxlength="255" class="w-full" placeholder="https://…" />
                            </div>
                        </div>
                    </PageCard>

                    <!-- SECTION : Identité visuelle -->
                    <PageCard title="Identité visuelle" subtitle="Logo utilisé sur les documents officiels et la plateforme.">
                        <template #header>
                            <i class="pi pi-image text-slate-400" aria-hidden="true"></i>
                        </template>
                        <div class="flex flex-col md:flex-row md:items-start gap-6">
                            <div class="flex flex-col items-center gap-3 flex-shrink-0">
                                <div class="w-36 h-36 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-center overflow-hidden">
                                    <img v-if="logoPreview" :src="logoPreview" alt="Aperçu du logo" class="max-w-full max-h-full object-contain" />
                                    <i v-else class="pi pi-building text-4xl text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                                </div>
                                <div class="flex items-center gap-2">
                                    <FileUpload mode="basic" :auto="false" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                chooseLabel="Changer" chooseIcon="pi pi-upload" @select="onLogoSelect"
                                                :invalid="!!form.errors.logo" class="!w-auto" />
                                    <Button v-if="logoPreview" icon="pi pi-trash" label="Retirer" severity="secondary"
                                            outlined size="small" @click="clearLogo" />
                                </div>
                                <p v-if="form.errors.logo" :class="errorClass" role="alert">{{ form.errors.logo }}</p>
                            </div>
                            <div class="flex-1 space-y-2">
                                <label for="logo_url" :class="labelClass">URL du logo</label>
                                <InputText id="logo_url" v-model="form.logo_url" maxlength="500" class="w-full" placeholder="https://…/logo.png" />
                                <p :class="hintClass">Ou collez une URL directe. Le fichier uploadé ci-contre est prioritaire et remplacera cette URL à l'enregistrement. PNG, JPG, WebP ou SVG — 2 Mo maximum.</p>
                            </div>
                        </div>
                    </PageCard>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                        <p class="text-xs text-slate-400 flex items-center gap-2">
                            <i class="pi pi-info-circle" aria-hidden="true"></i>
                            <span v-if="isSaved">Dernière sauvegarde : {{ formatDate(lastSavedAt) }}.</span>
                            <span v-else>Brouillon local — rien n'est enregistré tant que vous ne cliquez pas sur « Enregistrer ».</span>
                        </p>
                        <div class="flex items-center gap-3">
                            <Button type="button" label="Réinitialiser" icon="pi pi-refresh" severity="secondary"
                                    outlined @click="resetForm" />
                            <Button type="submit" label="Enregistrer les modifications" icon="pi pi-check"
                                    :loading="form.processing" />
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </AppLayout>
</template>
