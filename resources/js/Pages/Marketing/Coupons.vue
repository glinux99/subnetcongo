<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import debounce from 'lodash/debounce';

import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import ProgressBar from 'primevue/progressbar';
import Textarea from 'primevue/textarea';

const toast = useToast();
const confirmAction = useConfirmAction();

const props = defineProps({
    coupons: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10, last_page: 1 }) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({ total: 0, active: 0, total_usage: 0, expiring_soon: 0 }) },
});

// ============================================================
// FILTRES SERVEUR
// ============================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    type: props.filters?.type || null,
});

const searchLoading = ref(false);

const reload = debounce(() => {
    searchLoading.value = true;
    router.get(route('marketing.coupons.index'), { ...serverFilters, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => (searchLoading.value = false),
    });
}, 350);

watch(serverFilters, reload);

const resetFilters = () => {
    serverFilters.search = '';
    serverFilters.status = null;
    serverFilters.type = null;
};

const onPage = (event) => {
    router.get(route('marketing.coupons.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows,
    }, { preserveState: true, preserveScroll: true });
};

const statusOptions = [
    { label: 'Tous', value: null },
    { label: 'Actifs', value: 'active' },
    { label: 'Inactifs', value: 'inactive' },
    { label: 'Expirant', value: 'expiring' },
];

const typeOptions = [
    { label: 'Pourcentage (%)', value: 'percent', icon: 'pi pi-percentage' },
    { label: 'Montant fixe (USD)', value: 'fixed', icon: 'pi pi-dollar' },
];

const typeFilterOptions = [
    { label: 'Tous les types', value: null },
    ...typeOptions,
];

// ============================================================
// FORMULAIRE (création / édition / duplication)
// ============================================================
const couponDialog = ref(false);
const mode = ref('create'); // 'create' | 'edit'
const editingCode = ref(null);

const couponForm = useForm({
    code: '',
    description: '',
    discount_type: 'percent',
    discount_value: 10,
    usage_limit: null,
    max_uses_per_user: 1,
    min_amount: null,
    starts_at: null,
    expires_at: null,
    is_active: true,
});

const generateCode = () => {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = 'SERPENT-';
    for (let i = 0; i < 6; i++) {
        result += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
    }
    couponForm.code = result;
};

const openNew = () => {
    couponForm.reset();
    couponForm.clearErrors();
    mode.value = 'create';
    editingCode.value = null;
    couponDialog.value = true;
};

const fillForm = (coupon) => {
    couponForm.code = coupon.code;
    couponForm.description = coupon.description || '';
    couponForm.discount_type = coupon.discount_type;
    couponForm.discount_value = Number(coupon.discount_value);
    couponForm.usage_limit = coupon.usage_limit ? Number(coupon.usage_limit) : null;
    couponForm.max_uses_per_user = coupon.max_uses_per_user ? Number(coupon.max_uses_per_user) : 1;
    couponForm.min_amount = coupon.min_amount !== null && coupon.min_amount !== undefined ? Number(coupon.min_amount) : null;
    couponForm.starts_at = coupon.starts_at ? new Date(coupon.starts_at) : null;
    couponForm.expires_at = coupon.expires_at ? new Date(coupon.expires_at) : null;
    couponForm.is_active = !!coupon.is_active;
};

const openEdit = (coupon) => {
    couponForm.clearErrors();
    fillForm(coupon);
    mode.value = 'edit';
    editingCode.value = coupon.code;
    couponDialog.value = true;
};

const duplicateCoupon = (coupon) => {
    couponForm.clearErrors();
    fillForm(coupon);
    couponForm.code = '';
    generateCode();
    mode.value = 'create';
    editingCode.value = null;
    couponDialog.value = true;
};

const closeDialog = () => {
    couponDialog.value = false;
    couponForm.reset();
    couponForm.clearErrors();
};

const dialogTitle = computed(() => {
    if (mode.value === 'edit') return 'Modifier le coupon';
    return couponForm.code ? 'Créer un coupon' : 'Nouveau coupon';
});

const submitForm = () => {
    if (mode.value === 'edit' && editingCode.value) {
        couponForm.put(route('marketing.coupons.update', editingCode.value), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    } else {
        couponForm.post(route('marketing.coupons.store'), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    }
};

const confirmDelete = (coupon) => {
    const used = Number(coupon.used_count || 0);
    const alreadyUsed = used > 0;

    confirmAction({
        header: alreadyUsed ? 'Archiver le coupon' : 'Supprimer le coupon',
        message: alreadyUsed
            ? `Le coupon ${coupon.code} a déjà été utilisé ${used} fois. Il sera archivé (désactivé) plutôt que supprimé. Continuer ?`
            : `Supprimer définitivement le coupon ${coupon.code} ? Cette action est irréversible.`,
        acceptLabel: alreadyUsed ? 'Archiver' : 'Supprimer',
        danger: true,
        accept: () => router.delete(route('marketing.coupons.destroy', coupon.code), {
            preserveScroll: true,
        }),
    });
};

const copyCode = (code) => {
    navigator.clipboard?.writeText(code).then(() => {
        toast.add({ severity: 'info', summary: 'Copié', detail: `Code ${code} copié dans le presse-papier`, life: 2500 });
    }).catch(() => {
        toast.add({ severity: 'warn', summary: 'Copie impossible', detail: 'Le navigateur a bloqué l’accès au presse-papier.', life: 3000 });
    });
};

// ============================================================
// AFFICHAGE
// ============================================================
const formatMoney = (value) => {
    if (value === null || value === undefined) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(Number(value));
};

const formatDate = (value) => {
    if (!value) return 'Sans limite';
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

const effectiveStatus = (coupon) => {
    if (!coupon.is_active) return { label: 'Inactif', severity: 'secondary' };
    if (coupon.expires_at && new Date(coupon.expires_at) < new Date()) return { label: 'Expiré', severity: 'danger' };
    if (coupon.expires_at && (new Date(coupon.expires_at) - new Date()) < 7 * 24 * 3600 * 1000) {
        return { label: 'Expirant', severity: 'warning' };
    }
    return { label: 'Actif', severity: 'success' };
};

const usagePercent = (coupon) => {
    if (!coupon.usage_limit) return 0;
    return Math.min(100, Math.round((Number(coupon.used_count || 0) / Number(coupon.usage_limit)) * 100));
};

const couponList = computed(() => props.coupons?.data ?? []);
const firstRow = computed(() => ((props.coupons?.current_page || 1) - 1) * (props.coupons?.per_page || 10));
</script>

<template>
    <AppLayout>
        <Head title="Coupons marketing — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Codes promo"
                subtitle="Créez et pilotez les réductions appliquées à la marketplace"
                icon="pi-ticket"
                accent="blue"
                badge="Marketing"
                :actions="[{ label: 'Nouveau coupon', icon: 'pi-plus', variant: 'primary', onClick: openNew }]"
            />

            <div class="page-container page-section">
            <!-- KPI réels -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 my-6">
                <PageKpi label="Coupons au total" :value="stats.total" icon="pi-ticket" color="blue" />
                <PageKpi label="Coupons actifs" :value="stats.active" icon="pi-check-circle" color="emerald" />
                <PageKpi label="Utilisations cumulées" :value="stats.total_usage" icon="pi-chart-line" color="violet" />
                <PageKpi label="Expirant sous 7 jours" :value="stats.expiring_soon" icon="pi-clock" color="amber" />
            </div>

            <PageCard>
                <!-- Barre de filtres -->
                <div class="flex flex-col lg:flex-row lg:items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <span class="p-input-icon-left flex-1 min-w-[220px]">
                        <i class="pi pi-search text-slate-400" aria-hidden="true"></i>
                        <InputText
                            v-model="serverFilters.search"
                            placeholder="Rechercher un code ou une description…"
                            class="w-full"
                            aria-label="Rechercher un coupon"
                        />
                    </span>

                    <SelectButton
                        v-model="serverFilters.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        aria-label="Filtrer par statut"
                    />

                    <Select
                        v-model="serverFilters.type"
                        :options="typeFilterOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Tous les types"
                        class="min-w-[180px]"
                        showClear
                        aria-label="Filtrer par type"
                    />

                    <Button
                        label="Réinitialiser"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        @click="resetFilters"
                    />
                </div>

                <div class="flex items-center justify-between gap-3 pt-4 pb-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Catalogue des coupons</h2>
                        <Badge :value="coupons.total ?? 0" severity="info" />
                    </div>
                    <i v-if="searchLoading" class="pi pi-spin pi-spinner text-slate-400" aria-hidden="true"></i>
                </div>

                <DataTable
                    :value="couponList"
                    :lazy="true"
                    :paginator="true"
                    :rows="coupons.per_page || 10"
                    :first="firstRow"
                    :totalRecords="coupons.total ?? 0"
                    @page="onPage"
                    :loading="searchLoading"
                    stripedRows
                    responsiveLayout="scroll"
                    class="text-sm"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                >
                    <Column field="code" header="Code" style="min-width: 11rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 font-mono font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-500/10 border border-dashed border-blue-200 dark:border-blue-500/30 rounded-md tracking-wider">
                                    {{ data.code }}
                                </span>
                                <Button
                                    icon="pi pi-copy"
                                    text
                                    rounded
                                    severity="secondary"
                                    aria-label="Copier le code"
                                    v-tooltip.top="'Copier'"
                                    @click="copyCode(data.code)"
                                />
                            </div>
                            <p v-if="data.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1">{{ data.description }}</p>
                        </template>
                    </Column>

                    <Column field="discount_type" header="Type" style="min-width: 9rem">
                        <template #body="{ data }">
                            <span class="inline-flex items-center gap-1 text-slate-700 dark:text-slate-200 font-medium">
                                <i :class="['pi', data.discount_type === 'percent' ? 'pi-percentage' : 'pi-dollar', 'text-slate-400']" aria-hidden="true"></i>
                                {{ data.discount_type === 'percent' ? 'Pourcentage' : 'Montant fixe' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="discount_value" header="Valeur" style="min-width: 7rem">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-800 dark:text-slate-100">
                                {{ data.discount_type === 'percent' ? `${Number(data.discount_value)} %` : formatMoney(data.discount_value) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="used_count" header="Utilisations" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-slate-600 dark:text-slate-300">{{ data.used_count }} utilisés</span>
                                    <span class="text-slate-400">{{ data.usage_limit ? `sur ${data.usage_limit}` : 'illimité' }}</span>
                                </div>
                                <ProgressBar
                                    v-if="data.usage_limit"
                                    :value="usagePercent(data)"
                                    :showValue="false"
                                    style="height: 6px"
                                    class="rounded-full"
                                    :aria-label="`${usagePercent(data)} % de la limite atteinte`"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column field="min_amount" header="Min. achat" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300">{{ data.min_amount ? formatMoney(data.min_amount) : 'Aucun' }}</span>
                        </template>
                    </Column>

                    <Column field="starts_at" header="Début" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.starts_at) }}</span>
                        </template>
                    </Column>

                    <Column field="expires_at" header="Fin" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.expires_at) }}</span>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" style="min-width: 7rem">
                        <template #body="{ data }">
                            <Tag :value="effectiveStatus(data).label" :severity="effectiveStatus(data).severity" />
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen style="min-width: 9rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="secondary" aria-label="Éditer" v-tooltip.top="'Éditer'" @click="openEdit(data)" />
                                <Button icon="pi pi-copy" text rounded severity="secondary" aria-label="Dupliquer" v-tooltip.top="'Dupliquer'" @click="duplicateCoupon(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Supprimer" v-tooltip.top="'Supprimer'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-10 text-slate-500 dark:text-slate-400">
                            <i class="pi pi-ticket text-3xl mb-3 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                            <p class="font-semibold">Aucun coupon trouvé</p>
                            <p class="text-sm mt-1">Modifiez vos filtres ou créez un nouveau coupon.</p>
                        </div>
                    </template>
                </DataTable>
            </PageCard>
            </div>
        </div>

        <!-- Dialogue création / édition -->
        <Dialog
            v-model:visible="couponDialog"
            modal
            class="snc-dialog p-fluid"
            :style="{ width: '640px', maxWidth: '95vw' }"
        >
            <template #header>
                <span class="p-dialog-title flex items-center gap-2">
                    <i :class="['pi', mode === 'edit' ? 'pi-pencil' : 'pi-ticket']"></i>
                    {{ dialogTitle }}
                </span>
            </template>
            <div class="grid grid-cols-12 gap-4">
                <!-- Code -->
                <div class="col-span-12">
                    <label for="coupon-code" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Code</label>
                    <div class="flex gap-2">
                        <InputText
                            id="coupon-code"
                            v-model="couponForm.code"
                            placeholder="Laissez vide pour générer automatiquement (SERPENT-XXXXXX)"
                            class="font-mono"
                            :class="{ 'p-invalid': couponForm.errors.code }"
                            @blur="couponForm.code = (couponForm.code || '').toUpperCase()"
                        />
                        <Button
                            icon="pi pi-sparkles"
                            outlined
                            severity="secondary"
                            v-tooltip.top="'Générer un code aléatoire'"
                            aria-label="Générer un code aléatoire"
                            @click="generateCode"
                        />
                    </div>
                    <small v-if="couponForm.errors.code" class="p-error" role="alert">{{ couponForm.errors.code }}</small>
                </div>

                <!-- Description -->
                <div class="col-span-12">
                    <label for="coupon-desc" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Description</label>
                    <Textarea
                        id="coupon-desc"
                        v-model="couponForm.description"
                        rows="2"
                        autoResize
                        placeholder="Usage interne, ex. : campagne rentrée 2026"
                        :class="{ 'p-invalid': couponForm.errors.description }"
                    />
                    <small v-if="couponForm.errors.description" class="p-error" role="alert">{{ couponForm.errors.description }}</small>
                </div>

                <!-- Type -->
                <div class="col-span-12 md:col-span-6">
                    <label for="coupon-type" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Type de remise</label>
                    <Select
                        id="coupon-type"
                        v-model="couponForm.discount_type"
                        :options="typeOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                    <small v-if="couponForm.errors.discount_type" class="p-error" role="alert">{{ couponForm.errors.discount_type }}</small>
                </div>

                <!-- Valeur -->
                <div class="col-span-12 md:col-span-6">
                    <label for="coupon-value" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Valeur</label>
                    <InputNumber
                        id="coupon-value"
                        v-model="couponForm.discount_value"
                        :min="0"
                        :max="couponForm.discount_type === 'percent' ? 100 : undefined"
                        :suffix="couponForm.discount_type === 'percent' ? ' %' : ' USD'"
                        :minFractionDigits="0"
                        :maxFractionDigits="2"
                        class="w-full"
                        :class="{ 'p-invalid': couponForm.errors.discount_value }"
                    />
                    <small v-if="couponForm.errors.discount_value" class="p-error" role="alert">{{ couponForm.errors.discount_value }}</small>
                </div>

                <!-- Limite globale -->
                <div class="col-span-6 md:col-span-4">
                    <label for="coupon-limit" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Limite globale</label>
                    <InputNumber id="coupon-limit" v-model="couponForm.usage_limit" :min="1" allowEmpty placeholder="Illimitée" class="w-full" :class="{ 'p-invalid': couponForm.errors.usage_limit }" />
                    <small v-if="couponForm.errors.usage_limit" class="p-error" role="alert">{{ couponForm.errors.usage_limit }}</small>
                    <small v-else class="text-slate-400">Vide = illimitée</small>
                </div>

                <!-- Limite par utilisateur -->
                <div class="col-span-6 md:col-span-4">
                    <label for="coupon-per-user" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Limite / utilisateur</label>
                    <InputNumber id="coupon-per-user" v-model="couponForm.max_uses_per_user" :min="1" class="w-full" :class="{ 'p-invalid': couponForm.errors.max_uses_per_user }" />
                    <small v-if="couponForm.errors.max_uses_per_user" class="p-error" role="alert">{{ couponForm.errors.max_uses_per_user }}</small>
                </div>

                <!-- Min. achat -->
                <div class="col-span-12 md:col-span-4">
                    <label for="coupon-min" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Min. achat</label>
                    <InputNumber id="coupon-min" v-model="couponForm.min_amount" :min="0" allowEmpty :maxFractionDigits="2" placeholder="Aucun" suffix=" USD" class="w-full" :class="{ 'p-invalid': couponForm.errors.min_amount }" />
                    <small v-if="couponForm.errors.min_amount" class="p-error" role="alert">{{ couponForm.errors.min_amount }}</small>
                </div>

                <!-- Dates -->
                <div class="col-span-6">
                    <label for="coupon-start" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Date de début</label>
                    <DatePicker id="coupon-start" v-model="couponForm.starts_at" showIcon dateFormat="dd/mm/yy" :manualInput="false" class="w-full" :class="{ 'p-invalid': couponForm.errors.starts_at }" />
                    <small v-if="couponForm.errors.starts_at" class="p-error" role="alert">{{ couponForm.errors.starts_at }}</small>
                </div>

                <div class="col-span-6">
                    <label for="coupon-end" class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Date de fin</label>
                    <DatePicker id="coupon-end" v-model="couponForm.expires_at" showIcon dateFormat="dd/mm/yy" :manualInput="false" :minDate="couponForm.starts_at || undefined" class="w-full" :class="{ 'p-invalid': couponForm.errors.expires_at }" />
                    <small v-if="couponForm.errors.expires_at" class="p-error" role="alert">{{ couponForm.errors.expires_at }}</small>
                </div>

                <!-- Actif -->
                <div class="col-span-12 flex items-center justify-between gap-4 px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700">
                    <div>
                        <label for="coupon-active" class="font-semibold text-slate-800 dark:text-slate-100">Coupon actif</label>
                        <p class="text-xs text-slate-500">Un coupon inactif est refusé au paiement.</p>
                    </div>
                    <ToggleSwitch id="coupon-active" v-model="couponForm.is_active" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="closeDialog" />
                    <Button
                        :label="mode === 'edit' ? 'Enregistrer' : 'Créer le coupon'"
                        icon="pi pi-check"
                        :loading="couponForm.processing"
                        @click="submitForm"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
