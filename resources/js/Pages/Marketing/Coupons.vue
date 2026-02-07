<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import ProgressBar from 'primevue/progressbar';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import Slider from 'primevue/slider';
import InlineMessage from 'primevue/inlinemessage';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';

const { t } = useI18n();
const toast = useToast();

// --- STATE MANAGEMENT ---
const loading = ref(true);
const coupons = ref([]);
const displayCouponDialog = ref(false);
const displayStatsDialog = ref(false);
const submitted = ref(false);
const couponType = ref('percentage');

// Form Object
const coupon = ref({
    id: null,
    code: '',
    description: '',
    type: 'percentage',
    value: 10,
    min_purchase: 0,
    max_usage: 100,
    current_usage: 0,
    start_date: null,
    end_date: null,
    status: 'active',
    applied_to: [], // IDs des cours
    is_public: true
});

const couponTypes = ref([
    { label: 'Pourcentage (%)', value: 'percentage', icon: 'pi pi-percentage' },
    { label: 'Montant Fixe ($)', value: 'fixed', icon: 'pi pi-dollar' }
]);

const courseOptions = ref([
    { name: 'Mastering Vue 3', id: 1 },
    { name: 'Laravel Advanced Patterns', id: 2 },
    { name: 'UI/UX Design Fondamentaux', id: 3 },
    { name: 'Node.js Microservices', id: 4 },
    { name: 'Sécurité Web Congolaise', id: 5 }
]);

// --- FILTERS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// --- MOCK DATA ---
const loadCoupons = () => {
    loading.value = true;
    setTimeout(() => {
        coupons.value = [
            { 
                id: 1, code: 'WELCOME2026', description: 'Offre de bienvenue pour nouveaux inscrits', 
                type: 'percentage', value: 25, current_usage: 45, max_usage: 200, 
                start_date: '2025-01-01', end_date: '2026-12-31', status: 'active', revenue: 1250 
            },
            { 
                id: 2, code: 'BLACKFRIDAY', description: 'Vente flash novembre', 
                type: 'percentage', value: 50, current_usage: 150, max_usage: 150, 
                start_date: '2025-11-20', end_date: '2025-11-30', status: 'expired', revenue: 4500 
            },
            { 
                id: 3, code: 'SUBNET10', description: 'Réduction partenaire SubNet', 
                type: 'fixed', value: 10, current_usage: 12, max_usage: 500, 
                start_date: '2025-02-01', end_date: '2026-02-01', status: 'active', revenue: 120 
            },
            { 
                id: 4, code: 'TECHCONGO', description: 'Campagne influenceur Tech', 
                type: 'percentage', value: 15, current_usage: 89, max_usage: 300, 
                start_date: '2025-01-15', end_date: '2025-06-15', status: 'active', revenue: 2100 
            }
        ];
        loading.value = false;
    }, 1000);
};

// --- LOGIQUE METIER ---
const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    coupon.value.code = result;
};

const openNew = () => {
    coupon.value = { 
        id: null, code: '', description: '', type: 'percentage', value: 10, 
        min_purchase: 0, max_usage: 100, current_usage: 0, status: 'active', applied_to: [] 
    };
    submitted.value = false;
    displayCouponDialog.value = true;
};

const hideDialog = () => {
    displayCouponDialog.value = false;
    submitted.value = false;
};

const saveCoupon = () => {
    submitted.value = true;
    if (coupon.value.code.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Coupon enregistré avec succès', life: 3000 });
        displayCouponDialog.value = false;
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'info', summary: 'Copié', detail: 'Code coupon copié dans le presse-papier', life: 2000 });
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'scheduled': return 'info';
        default: return 'warning';
    }
};

// --- CALCULS KPI ---
const activeCouponsCount = computed(() => coupons.value.filter(c => c.status === 'active').length);
const totalSavedByUsers = computed(() => coupons.value.reduce((acc, curr) => acc + curr.revenue, 0).toLocaleString());

onMounted(() => {
    loadCoupons();
});
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Coupons - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER AVEC TAILWIND -->
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div class="flex items-center gap-4">
                    <div class="bg-indigo-600 p-4 rounded-2xl shadow-xl shadow-indigo-100 rotate-3 hover:rotate-0 transition-transform cursor-pointer">
                        <i class="pi pi-ticket text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Codes Coupons</h1>
                        <p class="text-slate-500 font-medium">Boostez vos ventes avec des campagnes promotionnelles</p>
                    </div>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <Button label="Nouvelle Promotion" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 flex-1 md:flex-none" @click="openNew" />
                </div>
            </header>

            <!-- KPI SECTION -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <Card v-for="kpi in [
                    { label: 'Coupons Actifs', val: activeCouponsCount, icon: 'pi-check-circle', color: 'indigo' },
                    { label: 'Économies Clients', val: totalSavedByUsers + ' $', icon: 'pi-wallet', color: 'emerald' },
                    { label: 'Taux d\'Utilisation', val: '64%', icon: 'pi-percentage', color: 'amber' },
                    { label: 'Coupons Expirés', val: '12', icon: 'pi-clock', color: 'rose' }
                ]" :key="kpi.label" class="border-none shadow-sm rounded-3xl overflow-hidden hover:shadow-md transition-shadow">
                    <template #content>
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">{{ kpi.label }}</span>
                                <div class="text-3xl font-black text-slate-800">{{ kpi.val }}</div>
                            </div>
                            <div :class="`p-3 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500` ">
                                <i :class="`pi ${kpi.icon} text-xl` "></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- MAIN DATA TABLE -->
            <Card class="border-none shadow-sm rounded-3xl overflow-hidden">
                <template #content>
                    <DataTable 
                        v-model:filters="filters"
                        :value="coupons" 
                        :paginator="true" 
                        :rows="10" 
                        :loading="loading"
                        stripedRows
                        responsiveLayout="scroll"
                        class="p-datatable-sm custom-coupon-table"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-bold text-slate-800">Catalogue de Remises</h2>
                                    <Badge :value="coupons.length" severity="info" />
                                </div>
                                <span class="p-input-icon-left w-full md:w-72">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Rechercher un coupon..." class="w-full rounded-xl border-slate-200" />
                                </span>
                            </div>
                        </template>

                        <Column field="code" header="CODE" style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="px-3 py-1.5 bg-slate-100 border-2 border-dashed border-slate-300 rounded-lg font-mono font-black text-indigo-600 tracking-wider text-sm">
                                        {{ data.code }}
                                    </div>
                                    <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-secondary p-button-sm" v-tooltip.top="'Copier'" @click="copyToClipboard(data.code)" />
                                </div>
                            </template>
                        </Column>

                        <Column field="type" header="TYPE / VALEUR" style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-lg">
                                        {{ data.value }}{{ data.type === 'percentage' ? '%' : ' $' }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">{{ data.type === 'percentage' ? 'De réduction' : 'Remise fixe' }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="usage" header="USAGE (LIMITES)" style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-600">{{ data.current_usage }} utilisés</span>
                                        <span class="text-slate-400">sur {{ data.max_usage }}</span>
                                    </div>
                                    <ProgressBar :value="(data.current_usage / data.max_usage) * 100" :showValue="false" style="height: 6px" class="rounded-full"
                                        :color="data.current_usage >= data.max_usage ? '#EF4444' : '#6366F1'" />
                                </div>
                            </template>
                        </Column>

                        <Column field="end_date" header="EXPIRATION" style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 text-sm">{{ new Date(data.end_date).toLocaleDateString() }}</span>
                                    <span class="text-[10px] text-rose-400 font-bold uppercase" v-if="data.status === 'expired'">Expiré</span>
                                    <span class="text-[10px] text-emerald-400 font-bold uppercase" v-else>Valide</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="revenue" header="REVENU RÉDUIT" style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-black text-emerald-600">{{ data.revenue }} $</span>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase px-4 py-1 text-[10px]" />
                            </template>
                        </Column>

                        <Column header="ACTIONS" alignFrozen="right" frozen style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" />
                                    <Button icon="pi pi-chart-bar" class="p-button-rounded p-button-text p-button-secondary" @click="displayStatsDialog = true" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- DIALOG: CREATE/EDIT COUPON -->
            <Dialog v-model:visible="displayCouponDialog" :modal="true" :style="{width: '650px'}" header="Configuration de la Promotion" class="p-fluid coupon-modal">
                <div class="grid grid-cols-12 gap-6 pt-4">
                    
                    <!-- Colonne Gauche -->
                    <div class="col-span-12 md:col-span-7 space-y-6">
                        <div class="field">
                            <label for="code" class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Code Coupon</label>
                            <div class="flex gap-2">
                                <InputText id="code" v-model="coupon.code" placeholder="ex: SUMMER25" class="font-mono font-bold border-2" :class="{'p-invalid': submitted && !coupon.code}" />
                                <Button icon="pi pi-refresh" @click="generateCode" class="p-button-outlined" v-tooltip="'Générer aléatoirement'" />
                            </div>
                            <small class="p-error" v-if="submitted && !coupon.code">Le code est obligatoire.</small>
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase">Type de Remise</label>
                            <SelectButton v-model="coupon.type" :options="couponTypes" optionLabel="label" optionValue="value" class="custom-select-button" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="font-black text-slate-700 text-sm mb-2 block uppercase">Valeur</label>
                                <InputNumber v-model="coupon.value" :suffix="coupon.type === 'percentage' ? '%' : ' $'" :min="0" :max="coupon.type === 'percentage' ? 100 : 1000" />
                            </div>
                            <div class="field">
                                <label class="font-black text-slate-700 text-sm mb-2 block uppercase">Limite d'usage</label>
                                <InputNumber v-model="coupon.max_usage" showButtons :min="1" />
                            </div>
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase">Appliquer à certains cours</label>
                            <MultiSelect v-model="coupon.applied_to" :options="courseOptions" optionLabel="name" placeholder="Tous les cours par défaut" display="chip" class="rounded-xl" />
                        </div>
                    </div>

                    <!-- Colonne Droite (Config Dates et Options) -->
                    <div class="col-span-12 md:col-span-5 bg-slate-50 p-6 rounded-3xl border border-slate-100 flex flex-col gap-6">
                        <div class="field">
                            <label class="font-black text-slate-400 text-[10px] mb-2 block uppercase">Date de début</label>
                            <Calendar v-model="coupon.start_date" showIcon class="w-full" />
                        </div>
                        <div class="field">
                            <label class="font-black text-slate-400 text-[10px] mb-2 block uppercase">Date d'expiration</label>
                            <Calendar v-model="coupon.end_date" showIcon class="w-full" />
                        </div>
                        
                        <Divider />

                        <div class="flex items-center justify-between p-3 bg-white rounded-2xl shadow-sm">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800 text-sm">Coupon Public</span>
                                <span class="text-[10px] text-slate-400">Visible sur le checkout</span>
                            </div>
                            <InputSwitch v-model="coupon.is_public" />
                        </div>

                        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 mt-auto">
                            <div class="flex gap-3 text-amber-600">
                                <i class="pi pi-info-circle text-xl"></i>
                                <p class="text-xs font-medium leading-relaxed">Les coupons expirés sont automatiquement désactivés à minuit de la date choisie.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-between items-center w-full border-t border-slate-100 pt-6">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold" @click="hideDialog" />
                        <Button label="Créer le Coupon" icon="pi pi-check" class="p-button-primary px-10 shadow-lg shadow-indigo-100" @click="saveCoupon" />
                    </div>
                </template>
            </Dialog>

            <!-- DIALOG: RAPPORT RAPIDE -->
            <Dialog v-model:visible="displayStatsDialog" :modal="true" :style="{width: '450px'}" header="Performance du Coupon" class="p-fluid">
                <div class="pt-4 space-y-8">
                    <div class="text-center">
                        <div class="inline-block px-4 py-2 bg-indigo-50 rounded-full text-indigo-600 font-black tracking-widest text-lg border-2 border-indigo-100 mb-2">
                            WELCOME2026
                        </div>
                        <p class="text-slate-400 text-sm">Géré depuis 14 mois</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100 text-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Inscriptions</span>
                            <span class="text-2xl font-black text-slate-800">145</span>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100 text-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Conversion</span>
                            <span class="text-2xl font-black text-emerald-500">12.4%</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-black text-slate-800 text-sm mb-4 flex items-center gap-2">
                            <i class="pi pi-chart-line text-indigo-500"></i> Économie cumulée par les élèves
                        </h4>
                        <div class="flex items-end gap-1 mb-2">
                            <span class="text-4xl font-black text-indigo-600">2,840.00</span>
                            <span class="text-xl font-bold text-indigo-400 pb-1">$</span>
                        </div>
                        <ProgressBar :value="75" :showValue="false" style="height: 10px" class="rounded-full" />
                    </div>

                    <div class="bg-slate-900 p-6 rounded-3xl text-white">
                        <h5 class="font-bold text-slate-400 text-xs uppercase mb-4 tracking-tighter">Profil type utilisateur</h5>
                        <div class="flex items-center gap-4">
                            <div class="flex -space-x-3">
                                <img src="https://i.pravatar.cc/150?u=1" class="w-8 h-8 rounded-full border-2 border-slate-900" />
                                <img src="https://i.pravatar.cc/150?u=2" class="w-8 h-8 rounded-full border-2 border-slate-900" />
                                <img src="https://i.pravatar.cc/150?u=3" class="w-8 h-8 rounded-full border-2 border-slate-900" />
                            </div>
                            <span class="text-xs font-medium text-slate-300">Étudiants de 1ère année (Bac+1)</span>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Exporter Rapport PDF" icon="pi pi-file-pdf" class="p-button-outlined p-button-secondary w-full border-2" />
                </template>
            </Dialog>

            <footer class="mt-12 text-center text-slate-400 text-sm pb-8 font-medium">
                © 2026 SubNetCongo LMS - Marketing & Loyalty Modules.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom PrimeVue Table Styling */
:deep(.p-datatable.custom-coupon-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border-bottom: 1px solid #F1F5F9;
    padding: 2rem 1rem;
}

:deep(.p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    text-transform: uppercase;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
}

:deep(.p-progressbar .p-progressbar-value) {
    background: #6366F1;
    border-radius: 10px;
}

/* Custom SelectButton Styling */
:deep(.custom-select-button .p-button) {
    border: 2px solid #F1F5F9;
    background: white;
    color: #64748B;
    font-weight: 700;
    font-size: 0.8rem;
}

:deep(.custom-select-button .p-button.p-highlight) {
    border-color: #6366F1;
    background: #F5F3FF;
    color: #6366F1;
}

/* Modal styling */
:deep(.p-dialog-header) {
    padding: 1.5rem;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-dialog-content) {
    padding: 0 1.5rem 1.5rem 1.5rem;
}

.field label {
    font-size: 0.75rem;
    color: #64748B;
}

/* Transitions */
.p-button {
    transition: all 0.3s ease;
}
</style>