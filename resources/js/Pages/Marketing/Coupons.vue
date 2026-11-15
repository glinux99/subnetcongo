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
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import ProgressBar from 'primevue/progressbar';
import Textarea from 'primevue/textarea';

const toast = useToast();
const confirmAction = useConfirmAction();
const { t } = useI18n();

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

const rowMenu = ref(null);
const rowMenuTarget = ref(null);

const toggleRowMenu = (event, row) => {
    rowMenuTarget.value = row;
    rowMenu.value?.toggle(event);
};

const rowMenuItems = computed(() => {
    const row = rowMenuTarget.value;
    if (!row) return [];
    return [
        { label: 'Éditer', icon: 'pi pi-pencil', command: () => openEdit(row) },
        { label: 'Dupliquer', icon: 'pi pi-copy', command: () => duplicateCoupon(row) },
        { separator: true },
        { label: 'Supprimer', icon: 'pi pi-trash', command: () => confirmDelete(row), class: 'text-red-600' },
    ];
});
</script>

<template>
    <AppLayout>
        <Head title="Coupons marketing — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Codes promo"
                subtitle="Créez et pilotez les réductions appliquées à la marketplace"
                icon="pi-ticket"
                accent="emerald"
                badge="Marketing"
                :actions="[{ label: 'Nouveau coupon', icon: 'pi-plus', variant: 'primary', onClick: openNew }]"
            />

            <div class="page-container page-section">
            <!-- KPI réels -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 my-6">
                <PageKpi label="Coupons au total" :value="stats.total" icon="pi-ticket" color="blue" />
                <PageKpi label="Coupons actifs" :value="stats.active" icon="pi-check-circle" color="emerald" />
                <PageKpi label="Utilisations cumulées" :value="stats.total_usage" icon="pi-chart-line" color="violet" />
                <PageKpi label="Expirant sous 7 jours" :value="stats.expiring_soon" icon="pi-clock" color="amber" />
            </div>

            <PageCard>
                <!-- Barre de filtres -->
                <div class="flex flex-col md:flex-row md:flex-wrap md:items-center gap-2 sm:gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <IconField iconPosition="left" class="w-full md:flex-1 md:min-w-[220px]">
                        <InputIcon class="pi pi-search text-slate-400" aria-hidden="true" />
                        <InputText
                            v-model="serverFilters.search"
                            placeholder="Rechercher un code ou une description…"
                            class="w-full h-11"
                            aria-label="Rechercher un coupon"
                        />
                    </IconField>

                    <SelectButton
                        v-model="serverFilters.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        aria-label="Filtrer par statut"
                        class="shrink-0 overflow-x-auto"
                        :pt="{ pcButton: { root: { class: 'h-11' } } }"
                    />

                    <Select
                        v-model="serverFilters.type"
                        :options="typeFilterOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Tous les types"
                        class="w-full md:w-44 h-11 shrink-0"
                        inputClass="!flex !items-center"
                        showClear
                        aria-label="Filtrer par type"
                    />

                    <Button
                        label="Réinitialiser"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        class="h-11 w-full md:w-auto shrink-0"
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
                    class="text-sm p-datatable-custom p-datatable-mobile border-none"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                >
                    <Column field="code" header="Code" style="min-width: 0">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2 px-1 py-1 min-w-0">
                                <span class="px-2 sm:px-3 py-1 font-mono font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-500/10 border border-dashed border-blue-200 dark:border-blue-500/30 rounded-md tracking-wider truncate">
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
                            <div class="md:hidden flex items-center gap-2 mt-1">
                                <span class="text-xs font-black text-slate-800 dark:text-slate-100">{{ data.discount_type === 'percent' ? `${Number(data.discount_value)} %` : formatMoney(data.discount_value) }}</span>
                                <Tag :value="effectiveStatus(data).label" :severity="effectiveStatus(data).severity" class="text-[9px]" />
                            </div>
                        </template>
                    </Column>

                    <Column field="discount_type" header="Type" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 9rem">
                        <template #body="{ data }">
                            <span class="inline-flex items-center gap-1 text-slate-700 dark:text-slate-200 font-medium">
                                <i :class="['pi', data.discount_type === 'percent' ? 'pi-percentage' : 'pi-dollar', 'text-slate-400']" aria-hidden="true"></i>
                                {{ data.discount_type === 'percent' ? 'Pourcentage' : 'Montant fixe' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="discount_value" header="Valeur" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" style="min-width: 7rem">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-800 dark:text-slate-100">
                                {{ data.discount_type === 'percent' ? `${Number(data.discount_value)} %` : formatMoney(data.discount_value) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="used_count" header="Utilisations" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 12rem">
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

                    <Column field="min_amount" header="Min. achat" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300">{{ data.min_amount ? formatMoney(data.min_amount) : 'Aucun' }}</span>
                        </template>
                    </Column>

                    <Column field="starts_at" header="Début" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.starts_at) }}</span>
                        </template>
                    </Column>

                    <Column field="expires_at" header="Fin" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 8rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.expires_at) }}</span>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" style="min-width: 7rem">
                        <template #body="{ data }">
                            <Tag :value="effectiveStatus(data).label" :severity="effectiveStatus(data).severity" />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 3.5rem; min-width: 3.5rem">
                        <template #body="{ data }">
                            <div class="hidden md:flex items-center gap-1 whitespace-nowrap">
                                <Button icon="pi pi-pencil" text rounded severity="secondary" aria-label="Éditer" v-tooltip.top="'Éditer'" @click="openEdit(data)" />
                                <Button icon="pi pi-copy" text rounded severity="secondary" aria-label="Dupliquer" v-tooltip.top="'Dupliquer'" @click="duplicateCoupon(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Supprimer" v-tooltip.top="'Supprimer'" @click="confirmDelete(data)" />
                            </div>
                            <Button
                                icon="pi pi-ellipsis-v"
                                class="p-button-text p-button-secondary p-button-rounded w-10 h-10 md:hidden text-slate-500"
                                :aria-label="t('pages.live_sessions.menu_more')"
                                :aria-haspopup="true"
                                @click="toggleRowMenu($event, data)"
                            />
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

        <Menu id="coupon-row-menu" ref="rowMenu" :model="rowMenuItems" popup :aria-label="t('pages.live_sessions.menu_more')" />

        <!-- Dialogue création / édition -->
     <SncDialog
    v-model:visible="couponDialog"
    :title="dialogTitle"
    subtitle="Configurez la remise commerciale, ses restrictions et sa période d'éligibilité"
    :icon="mode === 'edit' ? 'pi-pencil' : 'pi-ticket'"
    width="760px"
    @hide="closeDialog"
>
    <div class="space-y-6 py-1">

        <!-- ============================================================== -->
        <!-- LIVE TICKET PREVIEW (Effet Wow / Feedback Visuel Instantané)    -->
        <!-- ============================================================== -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-[#0B1120] to-slate-900 border border-slate-800 p-4 sm:p-5 shadow-xl">
            <!-- Cercles découpe coupon (effet ticket) -->
            <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#05070E] border-r border-slate-800"></div>
            <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#05070E] border-l border-slate-800"></div>
            
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-2">
                <div class="flex items-center gap-3.5 text-center sm:text-left">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500/20 to-rose-600/5 border border-rose-500/30 flex items-center justify-center text-rose-500 shrink-0">
                        <i class="pi pi-ticket text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 justify-center sm:justify-start">
                            <span class="font-mono text-base sm:text-lg font-black tracking-wider text-white">
                                {{ couponForm.code || 'SERPENT-XXXXXX' }}
                            </span>
                            <span v-if="couponForm.is_active" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <span class="w-1 h-1 rounded-full bg-emerald-400 animate-pulse"></span> ACTIF
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700/50 text-slate-400 border border-slate-600">
                                DÉSACTIVÉ
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ couponForm.description || 'Aucune note interne renseignée' }}
                        </p>
                    </div>
                </div>

                <!-- Montant Badge -->
                <div class="text-center sm:text-right bg-white/[0.04] sm:bg-transparent px-4 py-2 rounded-xl border border-white/5 sm:border-none w-full sm:w-auto">
                    <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold block">Réduction</span>
                    <span class="text-xl sm:text-2xl font-black text-rose-500 font-mono">
                        {{ couponForm.discount_value ? (couponForm.discount_type === 'percent' ? `-${couponForm.discount_value}%` : `-${couponForm.discount_value} $`) : '0.00' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 1 : CODE & IDENTIFIANT                                 -->
        <!-- ============================================================== -->
        <SncSection 
            icon="pi-key" 
            title="Identifiant & Description" 
            subtitle="Code d'activation requis par le client et usage interne" 
            icon-class="from-indigo-600 to-slate-800"
        >
            <div class="grid grid-cols-12 gap-4">
                <!-- Champ Code avec bouton intégré -->
                <div class="col-span-12">
                    <label for="coupon-code" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 flex items-center justify-between">
                        <span>Code du coupon <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-normal lowercase text-slate-400">Majuscules automatiques</span>
                    </label>
                    <div class="relative flex items-center">
                        <InputText
                            id="coupon-code"
                            v-model="couponForm.code"
                            placeholder="ex: RENTREE2026"
                            class="w-full font-mono font-bold tracking-wider uppercase pl-3.5 pr-28 h-11 text-sm bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl transition-all focus:ring-2 focus:ring-rose-500/20"
                            :class="{ '!border-rose-500': couponForm.errors.code }"
                            @blur="couponForm.code = (couponForm.code || '').toUpperCase().trim()"
                        />
                        <div class="absolute right-1.5 flex items-center">
                            <Button
                                icon="pi pi-sparkles"
                                label="Générer"
                                size="small"
                                severity="secondary"
                                text
                                class="!h-8 !px-2.5 !text-xs !font-bold hover:!bg-rose-500/10 hover:!text-rose-500 transition-colors"
                                v-tooltip.top="'Générer un code sécurisé aléatoire'"
                                @click="generateCode"
                            />
                        </div>
                    </div>
                    <small v-if="couponForm.errors.code" class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                        <i class="pi pi-exclamation-circle text-xs"></i> {{ couponForm.errors.code }}
                    </small>
                </div>

                <!-- Description -->
                <div class="col-span-12">
                    <label for="coupon-desc" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                        Description / Contexte
                    </label>
                    <Textarea
                        id="coupon-desc"
                        v-model="couponForm.description"
                        rows="2"
                        autoResize
                        placeholder="Précisez l'objectif : Campagne influenceurs, geste commercial, Black Friday..."
                        class="w-full text-sm rounded-xl bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-rose-500/20"
                        :class="{ '!border-rose-500': couponForm.errors.description }"
                    />
                    <small v-if="couponForm.errors.description" class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                        <i class="pi pi-exclamation-circle text-xs"></i> {{ couponForm.errors.description }}
                    </small>
                </div>
            </div>
        </SncSection>

        <!-- ============================================================== -->
        <!-- SECTION 2 : CONDITIONS TARIFAIRES                              -->
        <!-- ============================================================== -->
        <SncSection 
            icon="pi-percentage" 
            title="Valeur & Plafonds d'utilisation" 
            subtitle="Définissez l'avantage client et les seuils de rentabilité" 
            icon-class="from-emerald-500 to-teal-700"
        >
            <div class="grid grid-cols-12 gap-4">
                
                <!-- Type de remise (Style Toggle Segmenté moderne) -->
                <div class="col-span-12 sm:col-span-6">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                        Modèle de calcul
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <button
                            type="button"
                            @click="couponForm.discount_type = 'percent'"
                            :class="couponForm.discount_type === 'percent' 
                                ? 'bg-white dark:bg-slate-800 text-rose-500 font-bold shadow-sm border border-slate-200/50 dark:border-slate-700' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                            class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs transition-all"
                        >
                            <i class="pi pi-percentage text-xs"></i>
                            Pourcentage
                        </button>
                        <button
                            type="button"
                            @click="couponForm.discount_type = 'fixed'"
                            :class="couponForm.discount_type === 'fixed' 
                                ? 'bg-white dark:bg-slate-800 text-rose-500 font-bold shadow-sm border border-slate-200/50 dark:border-slate-700' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                            class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs transition-all"
                        >
                            <i class="pi pi-dollar text-xs"></i>
                            Montant Fixe
                        </button>
                    </div>
                </div>

                <!-- Montant / Valeur -->
                <div class="col-span-12 sm:col-span-6">
                    <label for="coupon-value" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 flex items-center justify-between">
                        <span>Montant déduit <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-semibold text-rose-500">
                            {{ couponForm.discount_type === 'percent' ? 'Max 100%' : 'En devises USD' }}
                        </span>
                    </label>
                    <InputNumber
                        id="coupon-value"
                        v-model="couponForm.discount_value"
                        :min="0"
                        :max="couponForm.discount_type === 'percent' ? 100 : undefined"
                        :suffix="couponForm.discount_type === 'percent' ? ' %' : ' $'"
                        :minFractionDigits="0"
                        :maxFractionDigits="2"
                        class="w-full !rounded-xl"
                        inputClass="!h-10 !text-sm !font-bold !bg-slate-50 dark:!bg-slate-900 !rounded-xl"
                        :class="{ '!border-rose-500': couponForm.errors.discount_value }"
                    />
                    <small v-if="couponForm.errors.discount_value" class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                        <i class="pi pi-exclamation-circle text-xs"></i> {{ couponForm.errors.discount_value }}
                    </small>
                </div>

                <!-- Ligne 2 : Limites & Montant Minimum -->
                <div class="col-span-12 grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    
                    <!-- Limite globale -->
                    <div class="p-3 rounded-xl bg-slate-50/80 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800">
                        <label for="coupon-limit" class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block mb-1">
                            Quota global
                        </label>
                        <InputNumber 
                            id="coupon-limit" 
                            v-model="couponForm.usage_limit" 
                            :min="1" 
                            allowEmpty 
                            placeholder="Illimité (∞)" 
                            class="w-full"
                            inputClass="!h-9 !text-xs !bg-transparent !border-0 !p-0 font-semibold focus:!ring-0"
                        />
                        <span class="text-[10px] text-slate-400 block border-t border-slate-200/60 dark:border-slate-800 pt-1 mt-1">Ex: 100 premiers acheteurs</span>
                    </div>

                    <!-- Limite / user -->
                    <div class="p-3 rounded-xl bg-slate-50/80 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800">
                        <label for="coupon-per-user" class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block mb-1">
                            Par client
                        </label>
                        <InputNumber 
                            id="coupon-per-user" 
                            v-model="couponForm.max_uses_per_user" 
                            :min="1" 
                            class="w-full"
                            inputClass="!h-9 !text-xs !bg-transparent !border-0 !p-0 font-semibold focus:!ring-0"
                        />
                        <span class="text-[10px] text-slate-400 block border-t border-slate-200/60 dark:border-slate-800 pt-1 mt-1">Nombre d'usages autorisés</span>
                    </div>

                    <!-- Panier minimum -->
                    <div class="p-3 rounded-xl bg-slate-50/80 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800">
                        <label for="coupon-min" class="text-[11px] font-bold uppercase text-slate-500 tracking-wider block mb-1">
                            Panier minimum
                        </label>
                        <InputNumber 
                            id="coupon-min" 
                            v-model="couponForm.min_amount" 
                            :min="0" 
                            allowEmpty 
                            suffix=" $"
                            placeholder="Aucun ($0)" 
                            class="w-full"
                            inputClass="!h-9 !text-xs !bg-transparent !border-0 !p-0 font-semibold focus:!ring-0"
                        />
                        <span class="text-[10px] text-slate-400 block border-t border-slate-200/60 dark:border-slate-800 pt-1 mt-1">Déclencheur d'activation</span>
                    </div>
                </div>

            </div>
        </SncSection>

        <!-- ============================================================== -->
        <!-- SECTION 3 : CALENDRIER & STATUT                                -->
        <!-- ============================================================== -->
        <SncSection 
            icon="pi-calendar-clock" 
            title="Fenêtre temporelle & Statut" 
            subtitle="Dates de prise d'effet et contrôle d'activité instantané" 
            icon-class="from-amber-500 to-rose-600"
        >
            <div class="grid grid-cols-12 gap-4">
                <!-- Date de Début -->
                <div class="col-span-12 sm:col-span-6">
                    <label for="coupon-start" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                        Date d'ouverture
                    </label>
                    <DatePicker 
                        id="coupon-start" 
                        v-model="couponForm.starts_at" 
                        showIcon 
                        dateFormat="dd/mm/yy" 
                        :manualInput="false" 
                        class="w-full" 
                        inputClass="!h-10 !text-sm !bg-slate-50 dark:!bg-slate-900 !rounded-xl"
                        :class="{ '!border-rose-500': couponForm.errors.starts_at }" 
                    />
                    <small v-if="couponForm.errors.starts_at" class="text-xs text-rose-500 mt-1 block">{{ couponForm.errors.starts_at }}</small>
                </div>

                <!-- Date d'Expiration -->
                <div class="col-span-12 sm:col-span-6">
                    <label for="coupon-end" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                        Date d'expiration
                    </label>
                    <DatePicker 
                        id="coupon-end" 
                        v-model="couponForm.expires_at" 
                        showIcon 
                        dateFormat="dd/mm/yy" 
                        :manualInput="false" 
                        :minDate="couponForm.starts_at || undefined" 
                        class="w-full" 
                        inputClass="!h-10 !text-sm !bg-slate-50 dark:!bg-slate-900 !rounded-xl"
                        :class="{ '!border-rose-500': couponForm.errors.expires_at }" 
                    />
                    <small v-if="couponForm.errors.expires_at" class="text-xs text-rose-500 mt-1 block">{{ couponForm.errors.expires_at }}</small>
                </div>

                <!-- Toggle Switch Carte Interactive -->
                <div class="col-span-12 flex items-center justify-between p-4 rounded-xl border transition-all duration-300"
                    :class="couponForm.is_active 
                        ? 'bg-emerald-500/[0.04] border-emerald-500/30 dark:bg-emerald-950/20' 
                        : 'bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800'">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                            :class="couponForm.is_active ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'">
                            <i :class="couponForm.is_active ? 'pi pi-check' : 'pi pi-power-off'" class="text-sm"></i>
                        </div>
                        <div>
                            <label for="coupon-active" class="font-bold text-sm text-slate-800 dark:text-slate-100 cursor-pointer">
                                {{ couponForm.is_active ? 'Coupon immédiatement activé' : 'Coupon désactivé / En pause' }}
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ couponForm.is_active ? 'Le coupon peut être appliqué au checkout dès maintenant.' : 'Le coupon sera refusé lors du paiement.' }}
                            </p>
                        </div>
                    </div>
                    <ToggleSwitch id="coupon-active" v-model="couponForm.is_active" />
                </div>

            </div>
        </SncSection>

    </div>

    <!-- ============================================================== -->
    <!-- FOOTER ACTIONS                                                 -->
    <!-- ============================================================== -->
    <template #footer>
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
            <Button 
                label="Annuler" 
                icon="pi pi-times" 
                text 
                severity="secondary" 
                class="!px-4 !py-2.5 !text-xs !font-bold rounded-xl"
                @click="closeDialog" 
            />
            <Button
                :label="mode === 'edit' ? 'Sauvegarder les modifications' : 'Créer et activer le coupon'"
                icon="pi pi-check"
                :loading="couponForm.processing"
                class="!px-5 !py-2.5 !text-xs !font-bold !bg-rose-600 hover:!bg-rose-700 !border-none !shadow-lg !shadow-rose-600/30 rounded-xl"
                @click="submitForm"
            />
        </div>
    </template>
</SncDialog>
    </AppLayout>
</template>
