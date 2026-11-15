<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO ERP - PAYMENT GATEWAYS MANAGER v40.0
 * ======================================================================================
 * Interface Ultimate : Design type Stripe, UX financière, Sticky Header.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

// --- PRIMEVUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputSwitch from 'primevue/inputswitch';
import Password from 'primevue/password';
import Badge from 'primevue/badge';

const { t, locale } = useI18n();
const toast = useToast();

const props = defineProps({
    gateways: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1 }) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({ total_volume: 0, total_transactions: 0, success_rate: 0, active_gateways: 0 }) },
    available_types: { type: Array, default: () => [] },
});

const loading = ref(false);
const displayGatewayDialog = ref(false);
const selectedGateway = ref(null);
const gatewayConfig = ref({
    api_key: '',
    secret_key: '',
    webhook_url: '',
    is_test_mode: true,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(locale.value, { style: 'currency', currency: 'USD' }).format(amount || 0);
};

const fetchGateways = () => {
    loading.value = true;
    router.get(route('system.payments'), { search: search.value, status: statusFilter.value }, {
        onFinish: () => { loading.value = false; },
        preserveState: true
    });
};

const toggleStatus = (gateway) => {
    // Optimistic UI update could go here
    router.post(route('system.payments.toggle', gateway.id), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: t('pages.payments.status_updated'), detail: t('pages.payments.gateway_now', { status: gateway.enabled ? t('pages.payments.status_active') : t('pages.payments.status_inactive') }), life: 3000 }),
        preserveScroll: true
    });
};

const confirmAction = useConfirmAction();
const deleteGateway = (gateway) => {
    confirmAction({
        header: t('pages.payments.delete_header'),
        message: t('pages.payments.delete_message'),
        acceptLabel: t('pages.payments.delete'),
        danger: true,
        accept: () => router.delete(route('system.payments.destroy', gateway.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: t('pages.payments.gateway_deleted'), life: 3000 }),
        }),
    });
};

const configureGateway = (gw) => {
    selectedGateway.value = gw;
    gatewayConfig.value = {
        api_key: gw.credentials?.api_key || '',
        secret_key: '', // Ne jamais renvoyer la clé secrète côté client
        webhook_url: gw.settings?.webhook_url || '',
        is_test_mode: gw.sandbox ?? true,
    };
    displayGatewayDialog.value = true;
};

const saveConfig = () => {
    toast.add({ severity: 'success', summary: t('pages.payments.config_saved'), detail: t('pages.payments.params_updated', { provider: selectedGateway.value?.provider }), life: 3000 });
    displayGatewayDialog.value = false;
};

const testConnection = (gateway) => {
    toast.add({ severity: 'info', summary: t('pages.payments.test_running'), detail: t('pages.payments.test_detail'), life: 2000 });
    router.post(route('system.payments.test', gateway.id), {}, {
        onSuccess: (page) => {
            const msg = page.props.flash?.success || t('pages.payments.connection_ok');
            toast.add({ severity: 'success', summary: t('pages.payments.test_success'), detail: msg, life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: t('pages.payments.test_failed'), detail: t('pages.payments.check_api_keys'), life: 4000 });
        }
    });
};

const exportTransactions = (gateway) => {
    window.location.href = route('system.payments.export', gateway.id);
};

// Map des icônes/couleurs selon le provider (Optionnel mais joli)
const getProviderStyle = (provider) => {
    const map = {
        'stripe': { icon: 'pi-credit-card', color: 'indigo' },
        'paypal': { icon: 'pi-paypal', color: 'blue' },
        'mobile_money': { icon: 'pi-mobile', color: 'emerald' },
    };
    return map[provider?.toLowerCase()] || { icon: 'pi-wallet', color: 'slate' };
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.payments.head_title')" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="t('pages.payments.page_title')"
                :subtitle="t('pages.payments.page_subtitle')"
                icon="pi-credit-card"
                accent="emerald"
                :badge="t('pages.payments.badge_system')"
                :actions="[
                    { label: t('pages.payments.refresh'), icon: 'pi-refresh', variant: 'secondary', onClick: fetchGateways },
                ]"
            />

            <div class="page-container page-section">
                <!-- --- KPI SECTION (Style Stripe) --- -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-8">
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ t('pages.payments.kpi_volume') }}</p>
                            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ formatCurrency(stats.total_volume) }}</div>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="pi pi-wallet text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ t('pages.payments.kpi_transactions') }}</p>
                            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_transactions }}</div>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="pi pi-arrow-right-arrow-left text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ t('pages.payments.kpi_success_rate') }}</p>
                            <div class="flex items-end gap-2">
                                <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ stats.success_rate }}%</div>
                                <span class="text-xs font-bold text-emerald-500 mb-1.5"><i class="pi pi-arrow-up text-[10px]"></i> 2.4%</span>
                            </div>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="pi pi-chart-line text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ t('pages.payments.kpi_active_gateways') }}</p>
                            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ stats.active_gateways }}</div>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <i class="pi pi-bolt text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- --- DATA TABLE --- -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    
                    <!-- Table Toolbar -->
                    <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">{{ t('pages.payments.routing_title') }}</h2>
                        <div class="flex gap-2 w-full md:w-auto">
                            <span class="relative w-full sm:w-64">
                                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <InputText v-model="search" :placeholder="t('pages.payments.search_placeholder')" class="w-full pl-10 !rounded-lg" @keyup.enter="fetchGateways" />
                            </span>
                            <Button icon="pi pi-filter" class="p-button-outlined p-button-secondary !rounded-lg" @click="fetchGateways" />
                        </div>
                    </div>

                    <DataTable :value="gateways.data" :paginator="true" :rows="10" :loading="loading" class="ultimate-table">
                        <Column field="provider" :header="t('pages.payments.col_provider')" sortable style="min-width: 16rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4">
                                    <div :class="`w-10 h-10 rounded-xl bg-${getProviderStyle(data.provider).color}-50 flex items-center justify-center text-${getProviderStyle(data.provider).color}-600 border border-${getProviderStyle(data.provider).color}-100`">
                                        <i :class="`pi ${getProviderStyle(data.provider).icon} text-lg`"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 capitalize">{{ data.provider }}</p>
                                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5">{{ data.type || 'Standard Gateway' }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="sandbox" :header="t('pages.payments.col_environment')" style="width: 12rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <span v-if="data.sandbox" class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span v-else class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-xs font-bold text-slate-600">{{ data.sandbox ? t('pages.payments.sandbox_test') : t('pages.payments.production') }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="enabled" :header="t('pages.payments.col_enabled')" style="width: 10rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <InputSwitch v-model="data.enabled" @change="toggleStatus(data)" />
                                    <span class="text-xs font-bold uppercase" :class="data.enabled ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ data.enabled ? t('pages.payments.enabled_active') : t('pages.payments.enabled_inactive') }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column :header="t('pages.payments.col_actions')" style="width: 8rem">
                            <template #body="{ data }">
                                <div class="flex gap-1 justify-end">
                                    <Button v-tooltip.top="t('pages.payments.config_api_tooltip')" icon="pi pi-cog" class="p-button-text p-button-secondary p-button-rounded" @click="configureGateway(data)" />
                                    <Button v-tooltip.top="t('pages.payments.test_connection_tooltip')" icon="pi pi-wifi" class="p-button-text p-button-info p-button-rounded" @click="testConnection(data)" />
                                    <Button v-tooltip.top="t('pages.payments.export_history_tooltip')" icon="pi pi-download" class="p-button-text p-button-secondary p-button-rounded" @click="exportTransactions(data)" />
                                    <div class="w-px h-6 bg-slate-200 my-auto mx-1"></div>
                                    <Button v-tooltip.top="t('pages.payments.delete')" icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded" @click="deleteGateway(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- --- DIALOG: CONFIGURATION GATEWAY --- -->
        <Dialog v-model:visible="displayGatewayDialog" modal :style="{ width: '550px' }" :header="t('pages.payments.config_gateway_header')">
            <div class="pt-2 space-y-6">
                <!-- En-tête du modal -->
                <div v-if="selectedGateway" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="flex items-center gap-3">
                        <div :class="`w-8 h-8 rounded-lg bg-${getProviderStyle(selectedGateway.provider).color}-100 flex items-center justify-center text-${getProviderStyle(selectedGateway.provider).color}-600`">
                            <i :class="`pi ${getProviderStyle(selectedGateway.provider).icon}`"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 capitalize text-sm">{{ selectedGateway.provider }}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest">ID: {{ selectedGateway.id }}</p>
                        </div>
                    </div>
                    <Tag :value="gatewayConfig.is_test_mode ? 'Sandbox' : 'Live'" :severity="gatewayConfig.is_test_mode ? 'warning' : 'success'" class="!text-[10px] uppercase font-bold" />
                </div>

                <!-- Formulaire -->
                <div class="space-y-5">
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">{{ t('pages.payments.public_key_label') }}</label>
                        <InputText v-model="gatewayConfig.api_key" class="w-full !rounded-lg font-mono text-sm" placeholder="pk_test_..." />
                    </div>
                    
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">{{ t('pages.payments.secret_key_label') }}</label>
                        <Password v-model="gatewayConfig.secret_key" :feedback="false" toggleMask class="w-full" inputClass="w-full !rounded-lg font-mono text-sm" placeholder="sk_test_..." />
                        <small class="text-slate-400 mt-1 block">{{ t('pages.payments.leave_empty') }}</small>
                    </div>

                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">{{ t('pages.payments.webhook_label') }}</label>
                        <InputText v-model="gatewayConfig.webhook_url" class="w-full !rounded-lg font-mono text-sm text-blue-600 bg-slate-50" readonly />
                        <small class="text-slate-400 mt-1 block">{{ t('pages.payments.copy_webhook') }}</small>
                    </div>
                </div>

                <!-- Mode Test Toggle -->
                <div class="flex items-center justify-between p-4 rounded-xl border transition-colors" 
                     :class="gatewayConfig.is_test_mode ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200'">
                    <div>
                        <span class="text-sm font-bold" :class="gatewayConfig.is_test_mode ? 'text-amber-900' : 'text-emerald-900'">
                            {{ gatewayConfig.is_test_mode ? t('pages.payments.test_mode_on') : t('pages.payments.test_mode_off') }}
                        </span>
                        <p class="text-xs mt-0.5" :class="gatewayConfig.is_test_mode ? 'text-amber-700' : 'text-emerald-700'">
                            {{ gatewayConfig.is_test_mode ? t('pages.payments.test_mode_on_desc') : t('pages.payments.test_mode_off_desc') }}
                        </p>
                    </div>
                    <InputSwitch v-model="gatewayConfig.is_test_mode" />
                </div>
            </div>
            
            <template #footer>
                <div class="flex justify-between items-center w-full pt-4 mt-2">
                    <Button :label="t('pages.payments.cancel')" class="p-button-text p-button-secondary font-bold" @click="displayGatewayDialog = false" />
                    <Button :label="t('pages.payments.save_config')" icon="pi pi-check" class="bg-slate-900 hover:bg-slate-800 text-white border-none !rounded-lg font-bold shadow-md px-6" @click="saveConfig" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* 
 * ULTIMATE DATATABLE DESIGN (Type Linear/Notion/Stripe)
 */
:deep(.ultimate-table .p-datatable-header) {
    display: none; 
}

:deep(.ultimate-table .p-datatable-thead > tr > th) {
    background: #ffffff;
    color: #64748b;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

:deep(.ultimate-table .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.25rem;
    color: #334155;
    transition: background-color 0.2s ease;
}

:deep(.ultimate-table .p-datatable-tbody > tr:hover > td) {
    background-color: #f8fafc;
}

/* Modals */
:deep(.p-dialog-header) {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.p-dialog-content) {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}
:deep(.p-dialog-footer) {
    border-top: none;
    padding: 0 1.5rem 1.5rem 1.5rem;
}
</style>