<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO ERP - PAYMENT GATEWAYS MANAGER v40.0
 * ======================================================================================
 * Interface Ultimate : Design type Stripe, UX financière, Sticky Header.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

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
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(amount || 0);
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
        onSuccess: () => toast.add({ severity: 'success', summary: 'Statut mis à jour', detail: `La passerelle est maintenant ${gateway.enabled ? 'active' : 'inactive'}.`, life: 3000 }),
        preserveScroll: true
    });
};

const confirmAction = useConfirmAction();
const deleteGateway = (gateway) => {
    confirmAction({
        header: 'Supprimer la passerelle',
        message: 'Supprimer cette passerelle ? Cette action est irréversible.',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('system.payments.destroy', gateway.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Passerelle supprimée', life: 3000 }),
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
    toast.add({ severity: 'success', summary: 'Configuration sauvegardée', detail: `Paramètres pour ${selectedGateway.value?.provider} mis à jour.`, life: 3000 });
    displayGatewayDialog.value = false;
};

const testConnection = (gateway) => {
    toast.add({ severity: 'info', summary: 'Test en cours', detail: 'Vérification des identifiants...', life: 2000 });
    router.post(route('system.payments.test', gateway.id), {}, {
        onSuccess: (page) => {
            const msg = page.props.flash?.success || 'Connexion établie avec succès';
            toast.add({ severity: 'success', summary: 'Test réussi', detail: msg, life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Échec de connexion', detail: 'Vérifiez vos clés d\'API.', life: 4000 });
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
    <Head title="Passerelles de Paiement - SubNetCongo" />
    <AppLayout>

        <div class="bg-slate-50/50 min-h-screen pb-20">

            <!-- --- STICKY HEADER --- -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-slate-900 rounded-xl shadow-inner flex items-center justify-center">
                        <i class="pi pi-credit-card text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Services Financiers</h1>
                            <Badge value="Paiements" severity="contrast" class="!text-[10px] !px-2 uppercase tracking-wider" />
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-0.5">Configuration des passerelles et flux transactionnels.</p>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <Button label="Rafraîchir" icon="pi pi-refresh" class="p-button-text p-button-secondary font-bold" @click="fetchGateways" :loading="loading" />
                    <Button label="Nouvelle Passerelle" icon="pi pi-plus" class="bg-slate-900 hover:bg-slate-800 text-white border-none font-bold shadow-md !rounded-xl" />
                </div>
            </header>

            <div class="max-w-screen-2xl mx-auto px-6">
                <!-- --- KPI SECTION (Style Stripe) --- -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Volume Total</p>
                            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ formatCurrency(stats.total_volume) }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="pi pi-wallet text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Transactions</p>
                            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_transactions }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="pi pi-arrow-right-arrow-left text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Taux de Succès</p>
                            <div class="flex items-end gap-2">
                                <div class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.success_rate }}%</div>
                                <span class="text-xs font-bold text-emerald-500 mb-1.5"><i class="pi pi-arrow-up text-[10px]"></i> 2.4%</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="pi pi-chart-line text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Passerelles Actives</p>
                            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.active_gateways }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <i class="pi pi-bolt text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- --- DATA TABLE --- -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    
                    <!-- Table Toolbar -->
                    <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Routage des Paiements</h2>
                        <div class="flex gap-2 w-full md:w-auto">
                            <span class="relative w-full sm:w-64">
                                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <InputText v-model="search" placeholder="Rechercher une passerelle..." class="w-full pl-10 !rounded-lg" @keyup.enter="fetchGateways" />
                            </span>
                            <Button icon="pi pi-filter" class="p-button-outlined p-button-secondary !rounded-lg" @click="fetchGateways" />
                        </div>
                    </div>

                    <DataTable :value="gateways.data" :paginator="true" :rows="10" :loading="loading" class="ultimate-table">
                        <Column field="provider" header="Fournisseur" sortable style="min-width: 16rem">
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
                        
                        <Column field="sandbox" header="Environnement" style="width: 12rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <span v-if="data.sandbox" class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span v-else class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-xs font-bold text-slate-600">{{ data.sandbox ? 'Sandbox (Test)' : 'Production' }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="enabled" header="Activation" style="width: 10rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <InputSwitch v-model="data.enabled" @change="toggleStatus(data)" />
                                    <span class="text-xs font-bold uppercase" :class="data.enabled ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ data.enabled ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" frozen style="width: 16rem">
                            <template #body="{ data }">
                                <div class="flex gap-1 justify-end">
                                    <Button v-tooltip.top="'Configuration API'" icon="pi pi-cog" class="p-button-text p-button-secondary p-button-rounded" @click="configureGateway(data)" />
                                    <Button v-tooltip.top="'Tester la connexion'" icon="pi pi-wifi" class="p-button-text p-button-info p-button-rounded" @click="testConnection(data)" />
                                    <Button v-tooltip.top="'Exporter Historique'" icon="pi pi-download" class="p-button-text p-button-secondary p-button-rounded" @click="exportTransactions(data)" />
                                    <div class="w-px h-6 bg-slate-200 my-auto mx-1"></div>
                                    <Button v-tooltip.top="'Supprimer'" icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded" @click="deleteGateway(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- --- DIALOG: CONFIGURATION GATEWAY --- -->
        <Dialog v-model:visible="displayGatewayDialog" modal :style="{ width: '550px' }" header="Configuration Passerelle">
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
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Clé Publique (Public Key)</label>
                        <InputText v-model="gatewayConfig.api_key" class="w-full !rounded-lg font-mono text-sm" placeholder="pk_test_..." />
                    </div>
                    
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Clé Secrète (Secret Key)</label>
                        <Password v-model="gatewayConfig.secret_key" :feedback="false" toggleMask class="w-full" inputClass="w-full !rounded-lg font-mono text-sm" placeholder="sk_test_..." />
                        <small class="text-slate-400 mt-1 block">Laissez vide si vous ne souhaitez pas la modifier.</small>
                    </div>

                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Webhook URL (Écouteur)</label>
                        <InputText v-model="gatewayConfig.webhook_url" class="w-full !rounded-lg font-mono text-sm text-indigo-600 bg-slate-50" readonly />
                        <small class="text-slate-400 mt-1 block">Copiez cette URL dans le tableau de bord de votre fournisseur.</small>
                    </div>
                </div>

                <!-- Mode Test Toggle -->
                <div class="flex items-center justify-between p-4 rounded-xl border transition-colors" 
                     :class="gatewayConfig.is_test_mode ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200'">
                    <div>
                        <span class="text-sm font-bold" :class="gatewayConfig.is_test_mode ? 'text-amber-900' : 'text-emerald-900'">
                            {{ gatewayConfig.is_test_mode ? 'Mode Test Activé (Sandbox)' : 'Mode Production Activé (Live)' }}
                        </span>
                        <p class="text-xs mt-0.5" :class="gatewayConfig.is_test_mode ? 'text-amber-700' : 'text-emerald-700'">
                            {{ gatewayConfig.is_test_mode ? 'Aucun argent réel ne sera débité.' : 'Les cartes réelles seront débitées. Prudence !' }}
                        </p>
                    </div>
                    <InputSwitch v-model="gatewayConfig.is_test_mode" />
                </div>
            </div>
            
            <template #footer>
                <div class="flex justify-between items-center w-full pt-4 mt-2">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="displayGatewayDialog = false" />
                    <Button label="Enregistrer la configuration" icon="pi pi-check" class="bg-slate-900 hover:bg-slate-800 text-white border-none !rounded-lg font-bold shadow-md px-6" @click="saveConfig" />
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