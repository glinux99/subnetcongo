<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ROLES & PERMISSIONS MANAGEMENT (ENTERPRISE UI)
 * ======================================================================================
 * Gestion complète (CRUD) des rôles et de leurs permissions associées.
 */

import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, reactive } from "vue";
import pkg from "lodash";
const { debounce, pickBy, cloneDeep } = pkg;

// ======================================================================================
// PRIMEVUE COMPONENTS
// ======================================================================================
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from "primevue/usetoast";
import Toast from 'primevue/toast';
import OverlayPanel from 'primevue/overlaypanel';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';
import Badge from 'primevue/badge';

// ======================================================================================
// PROPS & SERVICES
// ======================================================================================
const props = defineProps({
    title: { type: String, default: 'Gestion des Rôles' },
    filters: { type: Object, default: () => ({ search: '', field: '', order: '' }) },
    permissions: { type: Array, default: () => [] },
    roles: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
});

const toast = useToast();
const page = usePage();

// Helper global pour les permissions (à adapter selon votre implémentation Inertia)
const can = (permissions) => {
    // Remplacer par votre logique réelle. Ex: return page.props.auth.permissions.includes(permissions[0])
    return true;
};

// ======================================================================================
// ETATS (STATE)
// ======================================================================================
const op = ref();
const deleteDialog = ref(false);
const permissionDialog = ref(false);
const roleModalOpen = ref(false);
const isEditing = ref(false);
const selectedRole = ref(null);

const data = reactive({
    params: {
        search: props.filters.search,
        field: props.filters.field,
        order: props.filters.order,
    }
});

// Formulaire Inertia Unifié (Création & Modification)
const roleForm = useForm({
    id: null,
    name: '',
    guard_name: 'web',
    permissions: [], // Tableau d'IDs
});

// --- Statistiques ---
const stats = computed(() => ({
    total: props.roles.total || 0,
    fullAccess: props.roles.data.filter(r => r.permissions && r.permissions.length === props.permissions.length).length,
    customAccess: props.roles.data.filter(r => r.permissions && r.permissions.length > 0 && r.permissions.length < props.permissions.length).length,
}));

// --- Colonnes DataTable ---
const allColumns = [
    { field: 'name', header: 'Nom du Rôle', sortable: true },
    { field: 'guard_name', header: 'Guard', sortable: true },
    { field: 'permissions', header: 'Capacités Associées', sortable: false },
    { field: 'created_at', header: 'Date de Création', sortable: true },
];
const selectedColumnFields = ref(allColumns.map(col => col.field));
const displayedColumns = computed(() => allColumns.filter(col => selectedColumnFields.value.includes(col.field)));

// --- Recherche interne des permissions dans le modal ---
const permissionSearch = ref('');
const filteredPermissions = computed(() => {
    if (!permissionSearch.value) return props.permissions;
    return props.permissions.filter(p => p.name.toLowerCase().includes(permissionSearch.value.toLowerCase()));
});

// ======================================================================================
// LOGIQUE CRUD
// ======================================================================================

// 1. OPEN CREATE
const openCreate = () => {
    isEditing.value = false;
    roleForm.reset();
    roleForm.clearErrors();
    permissionSearch.value = '';
    roleModalOpen.value = true;
};

// 2. OPEN EDIT
const openEdit = (role) => {
    isEditing.value = true;
    roleForm.clearErrors();
    permissionSearch.value = '';

    // Remplissage du formulaire avec les données existantes
    roleForm.id = role.id;
    roleForm.name = role.name;
    roleForm.guard_name = role.guard_name;
    // Extraction des IDs des permissions actuelles du rôle
    roleForm.permissions = role.permissions.map(p => p.id);

    roleModalOpen.value = true;
};

// 3. SUBMIT (Create or Update)
const submitRole = () => {
    if (isEditing.value) {
        roleForm.put(route('role.update', roleForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                roleModalOpen.value = false;
                toast.add({ severity: 'success', summary: 'Mise à jour', detail: 'Le rôle a été modifié avec succès.', life: 3000 });
            },
        });
    } else {
        roleForm.post(route('role.store'), {
            preserveScroll: true,
            onSuccess: () => {
                roleModalOpen.value = false;
                roleForm.reset();
                toast.add({ severity: 'success', summary: 'Création', detail: 'Nouveau rôle créé avec succès.', life: 3000 });
            },
        });
    }
};

// 4. CONFIRM DELETE
const confirmDelete = (role) => {
    selectedRole.value = role;
    deleteDialog.value = true;
};

// 5. EXECUTE DELETE
const executeDelete = () => {
    router.delete(route("role.destroy", selectedRole.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialog.value = false;
            selectedRole.value = null;
            toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Le rôle a été retiré du système.', life: 3000 });
        },
    });
};

// ======================================================================================
// UTILITAIRES
// ======================================================================================

const toggleAllPermissions = () => {
    if (roleForm.permissions.length === props.permissions.length) {
        roleForm.permissions = []; // Désélectionner tout
    } else {
        roleForm.permissions = props.permissions.map(p => p.id); // Tout sélectionner
    }
};

const viewPermissions = (role) => {
    selectedRole.value = role;
    permissionDialog.value = true;
};

// Pagination & Recherche backend
const onPageChange = (event) => {
    router.get(route('role.index'), { page: event.page + 1 }, { preserveState: true });
};

watch(() => cloneDeep(data.params), debounce(() => {
    let params = pickBy(data.params);
    router.get(route("role.index"), params, { replace: true, preserveState: true });
}, 300));
</script>

<template>
    <AppLayout>
        <Head :title="props.title" />
        <Toast position="top-right" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER -->
            <!-- ============================================================================== -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-5">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-600 shadow-xl shadow-indigo-200">
                        <i class="pi pi-shield text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900">
                            {{ props.title }}
                        </h1>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Gouvernance & Privilèges (RBAC)</p>
                    </div>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <Button v-show="can(['create role'])" label="Créer un Rôle" icon="pi pi-plus"
                            class="p-button-primary shadow-lg shadow-indigo-500/30 font-black rounded-xl px-6 w-full md:w-auto" @click="openCreate" />
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- STATS CARDS -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:border-slate-300 transition-colors">
                    <div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Définis</div>
                        <div class="text-4xl font-black text-slate-800">{{ stats.total }}</div>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center border border-slate-100 group-hover:scale-110 transition-transform"><i class="pi pi-users text-2xl text-slate-500"></i></div>
                </div>

                <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:border-emerald-300 transition-colors">
                    <div>
                        <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Accès Super-Admin</div>
                        <div class="text-4xl font-black text-slate-800">{{ stats.fullAccess }}</div>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center border border-emerald-100 group-hover:scale-110 transition-transform"><i class="pi pi-verified text-2xl text-emerald-500"></i></div>
                </div>

                <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:border-amber-300 transition-colors">
                    <div>
                        <div class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Accès Limités</div>
                        <div class="text-4xl font-black text-slate-800">{{ stats.customAccess }}</div>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center border border-amber-100 group-hover:scale-110 transition-transform"><i class="pi pi-sliders-h text-2xl text-amber-500"></i></div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DATATABLE -->
            <!-- ============================================================================== -->
            <div class="overflow-hidden border border-slate-200 rounded-3xl bg-white shadow-sm">
                <DataTable lazy :value="roles.data" :rows="roles.per_page" :totalRecords="roles.total"
                           @page="onPageChange" paginator class="p-datatable-lg custom-datatable"
                           :first="(roles.current_page - 1) * roles.per_page" responsiveLayout="scroll">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4 bg-white border-b border-slate-100">
                            <IconField iconPosition="left" class="w-full md:w-96">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="data.params.search" placeholder="Rechercher par nom de rôle..." class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white text-sm font-medium" />
                            </IconField>
                            <Button icon="pi pi-sliders-h" class="p-button-rounded p-button-secondary p-button-text bg-slate-50" @click="op.toggle($event)" v-tooltip.left="'Affichage des colonnes'" />
                        </div>
                    </template>

                    <template #empty>
                        <div class="p-10 text-center text-slate-500 font-medium flex flex-col items-center">
                            <i class="pi pi-inbox text-4xl mb-4 text-slate-300"></i>
                            <p>Aucun rôle trouvé pour cette recherche.</p>
                        </div>
                    </template>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                        <template #body="{ data: rowData, field }">

                            <!-- NOM -->
                            <div v-if="field === 'name'" class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs" :class="rowData.name.toLowerCase() === 'admin' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-600'">
                                    {{ rowData.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-black text-slate-800 text-sm">{{ rowData.name }}</span>
                            </div>

                            <!-- GUARD -->
                            <Tag v-else-if="field === 'guard_name'" :value="rowData.guard_name" class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] font-black tracking-widest uppercase px-2 py-0.5" />

                            <!-- PERMISSIONS (Clickable) -->
                            <div v-else-if="field === 'permissions'" @click="viewPermissions(rowData)" class="cursor-pointer group inline-flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-200">
                                <Badge v-if="rowData.permissions.length === props.permissions.length" value="FULL ACCESS" severity="success" class="text-[9px] tracking-widest font-black" />
                                <div v-else class="flex items-center gap-2">
                                    <span class="text-xs font-black text-slate-600 group-hover:text-indigo-600">{{ rowData.permissions.length }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Règles</span>
                                </div>
                                <i class="pi pi-external-link text-[10px] text-slate-300 group-hover:text-indigo-400"></i>
                            </div>

                            <!-- DATE -->
                            <span v-else-if="field === 'created_at'" class="text-slate-500 text-xs font-bold flex items-center gap-2">
                                <i class="pi pi-calendar text-slate-400"></i> {{ new Date(rowData.created_at).toLocaleDateString() }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen style="width: 8rem" align="center">
                        <template #body="slotProps">
                            <div class="flex justify-center gap-2">
                                <Button v-show="can(['update role'])" icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600" @click="openEdit(slotProps.data)" v-tooltip.top="'Modifier'" />
                                <Button v-show="can(['delete role'])" icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger bg-rose-50 hover:bg-rose-100" @click="confirmDelete(slotProps.data)" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG : CREATE / UPDATE ROLE (Unified Form) -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="roleModalOpen" modal :style="{ width: '800px' }" class="p-fluid rounded-3xl custom-dialog bg-slate-50">
            <!-- Header personnalisé -->
            <template #header>
                <div class="flex items-center gap-4 w-full pr-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                        <i :class="isEditing ? 'pi pi-pencil' : 'pi pi-plus'" class="text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-black text-slate-800">{{ isEditing ? 'Modifier le Rôle' : 'Créer un Nouveau Rôle' }}</h2>
                        <p class="text-xs text-slate-500 font-medium">Définissez l'identité du rôle et attribuez ses privilèges de sécurité.</p>
                    </div>
                </div>
            </template>

            <div class="p-2 space-y-6 pt-4">
                <!-- Infos basiques -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Nom du rôle <span class="text-rose-500">*</span></label>
                        <InputText v-model="roleForm.name" class="w-full rounded-xl border-slate-300 font-bold p-3" placeholder="Ex: Rédacteur Web" autofocus />
                        <small v-if="roleForm.errors.name" class="text-rose-500 font-bold">{{ roleForm.errors.name }}</small>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Contexte (Guard)</label>
                        <div class="flex bg-slate-100 p-1 rounded-xl">
                            <button v-for="g in ['web', 'api']" :key="g" @click="roleForm.guard_name = g" type="button"
                                    :class="roleForm.guard_name === g ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                                    class="flex-1 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all border border-transparent">
                                {{ g }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gestion des Permissions -->
                <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col min-h-[400px]">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-800">Matrice des Privilèges</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ roleForm.permissions.length }} sélectionné(s)</p>
                        </div>
                        <Button :label="roleForm.permissions.length === props.permissions.length ? 'Tout vider' : 'Tout cocher'"
                                @click="toggleAllPermissions" class="p-button-text p-button-sm font-bold text-xs" />
                    </div>

                    <IconField iconPosition="left" class="mb-4">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText v-model="permissionSearch" placeholder="Rechercher une permission spécifique..." class="w-full rounded-xl bg-slate-50 border-transparent focus:bg-white text-sm" />
                    </IconField>

                    <div class="flex-1 bg-slate-50 rounded-xl border border-slate-200 p-2 overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 h-[300px] overflow-y-auto p-2 custom-scrollbar">
                            <div v-for="permission in filteredPermissions" :key="permission.id"
                                 class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer select-none"
                                 :class="roleForm.permissions.includes(permission.id) ? 'bg-indigo-50 border-indigo-200 shadow-sm' : 'bg-white border-slate-200 hover:border-slate-300'"
                                 @click="roleForm.permissions.includes(permission.id) ? roleForm.permissions = roleForm.permissions.filter(id => id !== permission.id) : roleForm.permissions.push(permission.id)">

                                <Checkbox v-model="roleForm.permissions" :inputId="'perm'+permission.id" :value="permission.id" @click.stop />
                                <div class="flex flex-col flex-1">
                                    <label :for="'perm'+permission.id" class="text-xs font-bold cursor-pointer" :class="roleForm.permissions.includes(permission.id) ? 'text-indigo-900' : 'text-slate-700'">{{ permission.name }}</label>
                                </div>
                            </div>
                            <div v-if="filteredPermissions.length === 0" class="col-span-full text-center p-8 text-slate-400 text-sm font-medium">
                                Aucune permission ne correspond à votre recherche.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center border-t border-slate-100 pt-4 mt-2">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="roleModalOpen = false" />
                    <Button :label="isEditing ? 'Mettre à jour le rôle' : 'Créer le rôle'" :icon="isEditing ? 'pi pi-save' : 'pi pi-check'"
                            class="p-button-primary px-8 font-black shadow-lg rounded-xl" @click="submitRole" :loading="roleForm.processing" :disabled="!roleForm.name.trim()" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : VIEW PERMISSIONS (Read-Only) -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="permissionDialog" modal :style="{ width: '600px' }" class="p-fluid rounded-3xl custom-dialog">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center"><i class="pi pi-lock-open text-lg"></i></div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">{{ selectedRole?.name }}</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Aperçu des privilèges</p>
                    </div>
                </div>
            </template>

            <div class="p-4 bg-slate-50 min-h-[300px]">
                <div v-if="selectedRole?.permissions.length === props.permissions.length" class="bg-emerald-50 border border-emerald-200 p-6 rounded-2xl text-center mb-4 flex flex-col items-center justify-center h-full">
                    <i class="pi pi-verified text-5xl text-emerald-500 mb-3"></i>
                    <h3 class="text-lg font-black text-emerald-800">Accès Administrateur Total</h3>
                    <p class="text-xs text-emerald-600 mt-1 font-medium">Ce rôle possède toutes les permissions disponibles dans le système.</p>
                </div>

                <div v-else-if="selectedRole?.permissions.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                    <div v-for="(perm, idx) in selectedRole.permissions" :key="idx" class="p-3 bg-white border border-slate-200 shadow-sm rounded-xl flex items-center gap-3">
                        <i class="pi pi-check text-emerald-500 text-sm"></i>
                        <span class="text-xs font-bold text-slate-700">{{ perm.name }}</span>
                    </div>
                </div>

                <div v-else class="text-center p-12 text-slate-400 font-medium border-2 border-dashed border-slate-200 rounded-2xl bg-white">
                    <i class="pi pi-lock text-4xl mb-3 text-slate-300"></i>
                    <p>Ce rôle n'a aucune permission d'action définie.</p>
                </div>
            </div>

            <template #footer>
                <div class="border-t border-slate-100 pt-4 flex justify-end">
                    <Button label="Fermer l'aperçu" class="p-button-secondary font-bold rounded-xl" @click="permissionDialog = false" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : CONFIRM DELETE -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="deleteDialog" modal :style="{ width: '450px' }" class="p-fluid rounded-3xl custom-dialog" :closable="false">
            <div class="p-6 flex flex-col items-center text-center pt-8">
                <div class="w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center mb-6 border border-rose-100">
                    <i class="pi pi-exclamation-triangle text-4xl text-rose-500"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">Suppression définitive</h2>
                <p class="text-sm text-slate-500 leading-relaxed mb-8">
                    Êtes-vous sûr de vouloir supprimer le rôle <span class="font-black text-rose-600">"{{ selectedRole?.name }}"</span> ? Les utilisateurs associés perdront ces privilèges immédiatement.
                </p>

                <div class="flex gap-3 w-full">
                    <Button label="Annuler" class="p-button-outlined p-button-secondary font-bold flex-1 rounded-xl py-3" @click="deleteDialog = false" />
                    <Button label="Oui, Supprimer" class="p-button-danger font-black flex-1 rounded-xl py-3 shadow-lg shadow-rose-500/30" @click="executeDelete" />
                </div>
            </div>
        </Dialog>

        <!-- OverlayPanel pour le choix des colonnes -->
        <OverlayPanel ref="op" class="p-4 rounded-2xl shadow-xl border border-slate-100">
            <div class="font-black mb-4 text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-100 pb-2">Affichage des colonnes</div>
            <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-full sm:w-64 rounded-xl border-slate-200" placeholder="Sélectionnez les colonnes" />
        </OverlayPanel>

    </AppLayout>
</template>

<style scoped>
/* ==========================================================================
   ENTERPRISE UI UTILITIES
   ========================================================================== */

/* DataTable Personnalisée */
:deep(.custom-datatable .p-datatable-header) { padding: 0; border: none; }
:deep(.custom-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.65rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.2rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.custom-datatable .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.custom-datatable .p-paginator) { border: none; padding-top: 1rem; }

/* Custom Dialogs */
:deep(.custom-dialog .p-dialog-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem;
    background: white;
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
}
:deep(.custom-dialog .p-dialog-content) { padding: 0; background: white; }

/* Scrollbars modernes (macOS style) */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }

/* Focus sans les bordures moches de base */
:deep(.p-inputtext:enabled:focus),
:deep(.p-multiselect:not(.p-disabled).p-focus) {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    border-color: #6366f1;
}

/* Checkbox alignment */
:deep(.p-checkbox .p-checkbox-box) { border-radius: 6px; }
:deep(.p-checkbox.p-highlight .p-checkbox-box) { background-color: #4f46e5; border-color: #4f46e5; }
</style>
