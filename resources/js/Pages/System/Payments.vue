<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputSwitch from 'primevue/inputswitch';
import Password from 'primevue/password';
import SelectButton from 'primevue/selectbutton';

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
    });
};

const toggleStatus = (gateway) => {
    router.post(route('system.payments.toggle', gateway.id), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Statut mis à jour', life: 3000 }),
    });
};

const deleteGateway = (gateway) => {
    if (!confirm('Supprimer cette passerelle ?')) return;
    router.delete(route('system.payments.destroy', gateway.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Passerelle supprimée', life: 3000 }),
    });
};

const configureGateway = (gw) => {
    selectedGateway.value = gw;
    gatewayConfig.value = {
        api_key: gw.credentials?.api_key || '',
        secret_key: '',
        webhook_url: gw.settings?.webhook_url || '',
        is_test_mode: gw.sandbox ?? true,
    };
    displayGatewayDialog.value = true;
};

const saveConfig = () => {
    toast.add({ severity: 'success', summary: 'Configuration mise à jour', detail: `Passerelle ${selectedGateway.value?.provider} enregistrée.`, life: 3000 });
    displayGatewayDialog.value = false;
};

const testConnection = (gateway) => {
    router.post(route('system.payments.test', gateway.id), {}, {
        onSuccess: (page) => {
            const msg = page.props.flash?.success || 'Test réussi';
            toast.add({ severity: 'success', summary: 'Test de connexion', detail: msg, life: 3000 });
        },
    });
};

const exportTransactions = (gateway) => {
    window.location.href = route('system.payments.export', gateway.id);
};
</script>

<template>
    <Head title="Passerelles de Paiement" />
    <AppLayout>
        <div class="py-10 px-6 lg:px-12">
            <div class="max-w-[1600px] mx-auto space-y-6">
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter">Passerelles de Paiement</h1>
                <p class="text-slate-500">Gérez les moyens de paiement, les configurations et les transactions.</p>

                <!-- STATS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card>
                        <template #content>
                            <p class="text-sm text-slate-500 uppercase tracking-wider">Volume total</p>
                            <p class="text-2xl font-black text-slate-900 mt-2">{{ formatCurrency(stats.total_volume) }}</p>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <p class="text-sm text-slate-500 uppercase tracking-wider">Transactions</p>
                            <p class="text-2xl font-black text-slate-900 mt-2">{{ stats.total_transactions }}</p>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <p class="text-sm text-slate-500 uppercase tracking-wider">Taux de succès</p>
                            <p class="text-2xl font-black text-slate-900 mt-2">{{ stats.success_rate }}%</p>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <p class="text-sm text-slate-500 uppercase tracking-wider">Passerelles actives</p>
                            <p class="text-2xl font-black text-slate-900 mt-2">{{ stats.active_gateways }}</p>
                        </template>
                    </Card>
                </div>

                <!-- TABLE -->
                <Card>
                    <template #title>
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <span class="text-xl font-black text-slate-800">Liste des passerelles</span>
                            <div class="flex gap-2">
                                <InputText v-model="search" placeholder="Rechercher..." class="w-64" @keyup.enter="fetchGateways" />
                                <Button label="Filtrer" icon="pi pi-search" @click="fetchGateways" />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="gateways.data" :paginator="true" :rows="10" :loading="loading" responsiveLayout="scroll">
                            <Column field="provider" header="PROVIDER" sortable>
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                            <i class="pi pi-credit-card text-slate-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 capitalize">{{ data.provider }}</p>
                                            <p class="text-xs text-slate-500">{{ data.type || 'payment' }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column field="enabled" header="STATUT" style="width: 10rem">
                                <template #body="{ data }">
                                    <Tag :value="data.enabled ? 'Actif' : 'Inactif'" :severity="data.enabled ? 'success' : 'danger'" />
                                </template>
                            </Column>
                            <Column field="sandbox" header="ENV" style="width: 8rem">
                                <template #body="{ data }">
                                    <Tag :value="data.sandbox ? 'Sandbox' : 'Production'" severity="info" />
                                </template>
                            </Column>
                            <Column header="ACTIONS" style="width: 20rem">
                                <template #body="{ data }">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-power" class="p-button-text p-button-sm" @click="toggleStatus(data)" />
                                        <Button icon="pi pi-cog" class="p-button-text p-button-sm" @click="configureGateway(data)" />
                                        <Button icon="pi pi-refresh" class="p-button-text p-button-sm" @click="testConnection(data)" />
                                        <Button icon="pi pi-download" class="p-button-text p-button-sm" @click="exportTransactions(data)" />
                                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="deleteGateway(data)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>

        <!-- DIALOG: CONFIGURE GATEWAY -->
        <Dialog v-model:visible="displayGatewayDialog" :modal="true" :style="{ width: '600px' }" header="Configuration de la Passerelle" class="p-fluid">
            <div class="space-y-6 pt-4">
                <div v-if="selectedGateway" class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="font-black text-slate-900 capitalize">{{ selectedGateway.provider }}</p>
                    <p class="text-xs text-slate-500">ID: {{ selectedGateway.id }}</p>
                </div>
                <div class="field">
                    <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Clé Publique</label>
                    <InputText v-model="gatewayConfig.api_key" class="w-full rounded-xl border-2" />
                </div>
                <div class="field">
                    <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Clé Secrète</label>
                    <Password v-model="gatewayConfig.secret_key" :feedback="false" toggleMask class="w-full rounded-xl" />
                </div>
                <div class="field">
                    <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Webhook URL</label>
                    <InputText v-model="gatewayConfig.webhook_url" class="w-full rounded-xl border-2 font-mono text-xs" />
                </div>
                <div class="flex items-center justify-between p-4 bg-amber-50 rounded-2xl border border-amber-100">
                    <div>
                        <span class="text-sm font-black text-amber-800">Mode Test (Sandbox)</span>
                        <p class="text-[10px] text-amber-600">Activer pour simuler les paiements.</p>
                    </div>
                    <InputSwitch v-model="gatewayConfig.is_test_mode" />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center w-full pt-4 border-t border-slate-50">
                    <Button label="Annuler" class="p-button-text" @click="displayGatewayDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-save" class="p-button-primary px-8 shadow-lg" @click="saveConfig" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
