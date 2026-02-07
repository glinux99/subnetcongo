<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

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
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import MultiSelect from 'primevue/multiselect';
import Divider from 'primevue/divider';
import Chip from 'primevue/chip';
import Editor from 'primevue/editor';
import InlineMessage from 'primevue/inlinemessage';
import ScrollPanel from 'primevue/scrollpanel';
import Menu from 'primevue/menu';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const threads = ref([]);
const selectedThreads = ref(null);
const displayThreadDialog = ref(false);
const displayCategoryDialog = ref(false);
const currentThread = ref(null);
const menu = ref();

// --- FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    category: { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const categories = ref([
    { label: 'Développement Web', value: 'WebDev', color: 'indigo', icon: 'pi-code' },
    { label: 'Intelligence Artificielle', value: 'AI', color: 'purple', icon: 'pi-bolt' },
    { label: 'Design & UI/UX', value: 'Design', color: 'pink', icon: 'pi-palette' },
    { label: 'Marketing Digital', value: 'Marketing', color: 'orange', icon: 'pi-megaphone' },
    { label: 'Entraide Générale', value: 'General', color: 'slate', icon: 'pi-comments' }
]);

// --- MOCK DATA GENERATION ---
const loadForumData = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const titles = [
            'Comment déployer Laravel sur un VPS ?',
            'Problème avec les props dans Vue 3',
            'Quelle tablette pour le design UI ?',
            'Conseils pour débuter en CyberSec',
            'Comprendre le SEO en 2026',
            'Les nouveautés de Tailwind CSS v4',
            'Erreur 404 sur mon projet Node.js'
        ];
        const authors = ['Alice Vasseur', 'Thomas Legrand', 'Sophie Martin', 'Jean-Pierre', 'Fatima', 'Marc'];

        for (let i = 1; i <= 50; i++) {
            const views = Math.floor(Math.random() * 5000);
            const replies = Math.floor(Math.random() * 80);
            const catIdx = Math.floor(Math.random() * categories.value.length);

            data.push({
                id: 100 + i,
                title: titles[i % titles.length],
                author: {
                    name: authors[i % authors.length],
                    avatar: `https://i.pravatar.cc/150?u=forum${i}`,
                    rank: i % 5 === 0 ? 'Expert' : 'Étudiant'
                },
                category: categories.value[catIdx].value,
                views: views,
                replies: replies,
                is_pinned: i % 12 === 0,
                is_locked: i % 15 === 0,
                is_resolved: i % 3 === 0,
                status: i % 20 === 0 ? 'reported' : 'active',
                created_at: 'Il y a ' + (i % 24) + 'h',
                last_reply: {
                    user: authors[(i+1) % authors.length],
                    date: 'Dernier message il y a 5 min'
                },
                content: 'Ceci est le contenu du message original pour le test du forum SubNetCongo...'
            });
        }
        threads.value = data;
        loading.value = false;
    }, 1200);
};

// --- ACTIONS ---
const viewThread = (data) => {
    currentThread.value = data;
    displayThreadDialog.value = true;
};

const getCategoryLabel = (val) => {
    return categories.value.find(c => c.value === val)?.label || val;
};

const getCategoryColor = (val) => {
    return categories.value.find(c => c.value === val)?.color || 'slate';
};

const togglePin = (thread) => {
    thread.is_pinned = !thread.is_pinned;
    toast.add({ severity: 'info', summary: 'Mis à jour', detail: thread.is_pinned ? 'Sujet épinglé' : 'Sujet désépinglé', life: 2000 });
};

const toggleLock = (thread) => {
    thread.is_locked = !thread.is_locked;
    toast.add({ severity: 'warn', summary: 'Statut', detail: thread.is_locked ? 'Sujet verrouillé' : 'Sujet ouvert', life: 2000 });
};

const resolveThread = (thread) => {
    thread.is_resolved = true;
    displayThreadDialog.value = false;
    toast.add({ severity: 'success', summary: 'Résolu', detail: 'Sujet marqué comme résolu !', life: 3000 });
};

// --- CALCULS KPI ---
const activeThreads = computed(() => threads.value.filter(t => !t.is_locked).length);
const reportedCount = computed(() => threads.value.filter(t => t.status === 'reported').length);

onMounted(() => {
    loadForumData();
});
</script>

<template>
    <AppLayout>
        <Head title="Forum Communautaire - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 rotate-3 transition-transform hover:rotate-0">
                        <i class="pi pi-comments text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Espace Communautaire</h1>
                        <p class="text-slate-500 font-medium italic">Gérez les échanges et favorisez l'entraide entre apprenants</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Gérer Catégories" icon="pi pi-tags" class="p-button-outlined p-button-indigo border-2 font-bold" @click="displayCategoryDialog = true" />
                    <Button label="Nouvelle Discussion" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" />
                </div>
            </header>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Sujets Actifs', val: activeThreads, icon: 'pi-list', color: 'indigo' },
                    { label: 'Total Réponses', val: '14,280', icon: 'pi-reply', color: 'emerald' },
                    { label: 'Sujets Signalés', val: reportedCount, icon: 'pi-exclamation-triangle', color: 'rose' },
                    { label: 'Membres en ligne', val: '284', icon: 'pi-users', color: 'amber' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500 group-hover:scale-110 transition-transform` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">

                <!-- MAIN FORUM LIST -->
                <div class="col-span-12 lg:col-span-9">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden">
                        <template #content>
                            <DataTable
                                v-model:filters="filters"
                                :value="threads"
                                :paginator="true"
                                :rows="12"
                                dataKey="id"
                                :loading="loading"
                                stripedRows
                                filterDisplay="menu"
                                :globalFilterFields="['title', 'author.name']"
                                class="p-datatable-lg custom-forum-table"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Fils de discussion</h2>
                                            <Badge :value="threads.length" severity="info" />
                                        </div>
                                        <div class="flex gap-3 w-full md:w-auto">
                                            <span class="p-input-icon-left flex-1 md:w-80">
                                                <i class="pi pi-search" />
                                                <InputText v-model="filters['global'].value" placeholder="Rechercher un sujet ou un auteur..." class="w-full rounded-2xl border-slate-200" />
                                            </span>
                                            <MultiSelect v-model="filters['category'].value" :options="categories" optionLabel="label" optionValue="value" placeholder="Catégories" class="w-48 rounded-2xl" />
                                        </div>
                                    </div>
                                </template>

                                <Column style="width: 3rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1 items-center">
                                            <i v-if="data.is_pinned" class="pi pi-thumbtack text-orange-500 text-xs" v-tooltip="'Épinglé'"></i>
                                            <i v-if="data.is_locked" class="pi pi-lock text-slate-400 text-xs" v-tooltip="'Verrouillé'"></i>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="title" header="DISCUSSION" style="min-width: 25rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-slate-800 text-base hover:text-indigo-600 cursor-pointer transition-colors" @click="viewThread(data)">{{ data.title }}</span>
                                                <Tag v-if="data.is_resolved" value="RÉSOLU" severity="success" class="text-[9px] px-2" />
                                                <Tag v-if="data.status === 'reported'" value="MODÉRATION" severity="danger" class="text-[9px] px-2" />
                                            </div>
                                            <div class="flex items-center gap-3 text-xs font-medium text-slate-400">
                                                <span :class="`text-${getCategoryColor(data.category)}-500 font-bold uppercase` ">#{{ data.category }}</span>
                                                <span>Par <b class="text-slate-600">{{ data.author.name }}</b></span>
                                                <span>• {{ data.created_at }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="ENGAGEMENT" style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col items-center">
                                                <span class="font-black text-slate-800">{{ data.replies }}</span>
                                                <span class="text-[9px] font-bold text-slate-400 uppercase">Réponses</span>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="font-black text-slate-400">{{ (data.views / 1000).toFixed(1) }}k</span>
                                                <span class="text-[9px] font-bold text-slate-400 uppercase">Vues</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="DERNIER MESSAGE" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <Avatar :image="`https://i.pravatar.cc/150?u=${data.last_reply.user}`" shape="circle" size="small" />
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-bold text-slate-700">{{ data.last_reply.user }}</span>
                                                <span class="text-[9px] text-slate-400 italic">{{ data.last_reply.date }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="ACTIONS" alignFrozen="right" frozen style="width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-1">
                                            <Button icon="pi pi-thumbtack" :class="`p-button-rounded p-button-text ${data.is_pinned ? 'text-orange-500' : 'text-slate-300'}` " @click="togglePin(data)" />
                                            <Button icon="pi pi-lock" :class="`p-button-rounded p-button-text ${data.is_locked ? 'text-indigo-600' : 'text-slate-300'}` " @click="toggleLock(data)" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </div>

                <!-- SIDEBAR TOOLS -->
                <div class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- TOP CONTRIBUTORS -->
                    <Card class="border-none shadow-sm rounded-3xl bg-slate-900 text-white overflow-hidden">
                        <template #title>
                            <div class="px-4 pt-2">
                                <span class="text-base font-black uppercase tracking-widest text-indigo-400">Top Contributeurs</span>
                                <p class="text-xs text-slate-400 font-medium">Les plus actifs ce mois-ci</p>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-6">
                                <div v-for="(user, idx) in [
                                    { name: 'Alice Vasseur', posts: 145, xp: 2800 },
                                    { name: 'David Nkulu', posts: 92, xp: 1950 },
                                    { name: 'Sarah Mendy', posts: 78, xp: 1600 }
                                ]" :key="user.name" class="flex items-center gap-4 group">
                                    <div class="relative">
                                        <Avatar :image="`https://i.pravatar.cc/150?u=${idx}`" shape="circle" size="large" class="border-2 border-indigo-500" />
                                        <div class="absolute -bottom-1 -right-1 bg-amber-400 text-slate-900 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black">
                                            {{ idx + 1 }}
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-black text-sm">{{ user.name }}</p>
                                        <div class="flex justify-between items-center text-[10px] text-slate-400">
                                            <span>{{ user.posts }} posts</span>
                                            <span class="text-emerald-400 font-bold">{{ user.xp }} XP</span>
                                        </div>
                                        <div class="w-full bg-slate-800 h-1 rounded-full mt-1 overflow-hidden">
                                            <div class="bg-indigo-500 h-full" :style="{ width: (100 - idx*20) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <Button label="Voir le classement complet" class="p-button-text p-button-sm p-button-white w-full mt-6 text-xs" />
                        </template>
                    </Card>

                    <!-- TRENDING TAGS -->
                    <Card class="border-none shadow-sm rounded-3xl">
                        <template #title><span class="text-base font-black text-slate-800 px-4">Sujets Chauds 🔥</span></template>
                        <template #content>
                            <div class="flex flex-wrap gap-2">
                                <Chip label="Laravel 11" class="text-xs font-bold bg-indigo-50 text-indigo-600" />
                                <Chip label="Vue 3.5" class="text-xs font-bold bg-emerald-50 text-emerald-600" />
                                <Chip label="Docker VPS" class="text-xs font-bold bg-blue-50 text-blue-600" />
                                <Chip label="ChatGPT API" class="text-xs font-bold bg-purple-50 text-purple-600" />
                                <Chip label="UI Design" class="text-xs font-bold bg-pink-50 text-pink-600" />
                                <Chip label="Freelance" class="text-xs font-bold bg-orange-50 text-orange-600" />
                            </div>
                        </template>
                    </Card>

                    <!-- MODERATION ALERT -->
                    <div class="p-6 bg-rose-50 rounded-[2rem] border border-rose-100 flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-shield text-rose-500 text-2xl"></i>
                            <h4 class="font-black text-rose-900 text-lg">Alertes</h4>
                        </div>
                        <p class="text-sm text-rose-700 font-medium">
                            Vous avez <b>{{ reportedCount }}</b> messages signalés en attente de révision par l'équipe de modération.
                        </p>
                        <Button label="Accéder à la modération" severity="danger" class="w-full shadow-lg shadow-rose-200 font-bold rounded-xl" />
                    </div>

                </div>
            </div>

            <!-- MODAL: VIEW THREAD & MODERATE -->
            <Dialog v-model:visible="displayThreadDialog" :modal="true" :style="{ width: '850px' }" header="Détails du Sujet" class="p-fluid forum-modal" v-if="currentThread">
                <div class="p-2">
                    <!-- Original Post -->
                    <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 mb-8">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <Avatar :image="currentThread.author.avatar" size="xlarge" shape="circle" class="border-4 border-white shadow-sm" />
                                <div>
                                    <h2 class="text-2xl font-black text-slate-800">{{ currentThread.title }}</h2>
                                    <p class="text-slate-500 font-medium italic">Par {{ currentThread.author.name }} • <span class="text-indigo-500">{{ currentThread.author.rank }}</span></p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <Tag :value="currentThread.category" :class="`bg-${getCategoryColor(currentThread.category)}-100 text-${getCategoryColor(currentThread.category)}-600 uppercase font-black text-[10px] px-3` " />
                                <span class="text-xs text-slate-400">{{ currentThread.created_at }}</span>
                            </div>
                        </div>
                        <ScrollPanel style="width: 100%; height: 150px">
                            <p class="text-slate-700 leading-relaxed text-sm whitespace-pre-line">{{ currentThread.content }}</p>
                        </ScrollPanel>
                    </div>

                    <!-- Moderate Section -->
                    <TabView class="custom-forum-tabs">
                        <TabPanel header="Réponses (12)">
                            <div class="space-y-4 py-4 max-h-[400px] overflow-y-auto pr-2">
                                <div v-for="i in 3" :key="i" class="p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex items-center gap-2">
                                            <Avatar icon="pi pi-user" shape="circle" size="small" />
                                            <span class="text-xs font-black text-slate-800">Utilisateur_{{ i }}</span>
                                        </div>
                                        <Button icon="pi pi-ellipsis-h" class="p-button-text p-button-secondary p-button-sm p-0" />
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed">Merci pour cette question ! J'ai rencontré le même problème et la solution était de mettre à jour la version de NPM.</p>
                                    <div class="mt-3 flex gap-4 text-[10px] font-bold text-slate-400">
                                        <span><i class="pi pi-thumbs-up mr-1"></i> 14 Likes</span>
                                        <span class="cursor-pointer hover:text-rose-500"><i class="pi pi-flag mr-1"></i> Signaler</span>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel header="Actions Modérateur">
                            <div class="grid grid-cols-2 gap-6 py-6">
                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Contrôle du Sujet</h4>
                                    <div class="flex flex-col gap-3">
                                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                                            <span class="text-sm font-bold text-slate-700">Verrouiller la discussion</span>
                                            <InputSwitch v-model="currentThread.is_locked" />
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                                            <span class="text-sm font-bold text-slate-700">Épingler en haut de liste</span>
                                            <InputSwitch v-model="currentThread.is_pinned" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Maintenance</h4>
                                    <div class="flex flex-col gap-2">
                                        <Button label="Déplacer vers une autre catégorie" icon="pi pi-external-link" class="p-button-outlined p-button-indigo p-button-sm" />
                                        <Button label="Supprimer définitivement" icon="pi pi-trash" class="p-button-outlined p-button-danger p-button-sm" />
                                        <Button label="Bannir l'auteur (Temporaire)" icon="pi pi-ban" class="p-button-text p-button-danger p-button-sm font-bold" />
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                                <i class="pi pi-info-circle text-amber-500 mt-1"></i>
                                <p class="text-[11px] font-medium text-amber-700 leading-relaxed">
                                    Toute action de modération est enregistrée dans le journal système. L'utilisateur recevra une notification pour expliquer la modification de son sujet.
                                </p>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full border-t border-slate-100 pt-6 px-2">
                        <Button label="Marquer comme Signalé" icon="pi pi-flag" class="p-button-text p-button-danger font-bold" />
                        <div class="flex gap-2">
                            <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="displayThreadDialog = false" />
                            <Button label="Marquer comme Résolu" icon="pi pi-check-circle" class="p-button-success px-8 shadow-lg shadow-emerald-100 font-black" @click="resolveThread(currentThread)" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: MANAGE CATEGORIES -->
            <Dialog v-model:visible="displayCategoryDialog" :modal="true" :style="{ width: '500px' }" header="🛠️ Configuration des Catégories" class="p-fluid">
                <div class="space-y-6 pt-4">
                    <InlineMessage severity="info" class="w-full">Créez des espaces thématiques pour organiser les questions des élèves.</InlineMessage>
                    <div class="max-h-[400px] overflow-y-auto pr-2 space-y-4">
                        <div v-for="cat in categories" :key="cat.value" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div :class="`p-3 rounded-xl bg-${cat.color}-100 text-${cat.color}-600` ">
                                    <i :class="`pi ${cat.icon} text-xl` "></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 uppercase text-xs tracking-widest">{{ cat.label }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ cat.value }}</span>
                                </div>
                            </div>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Button icon="pi pi-pencil" class="p-button-text p-button-secondary p-button-sm" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" />
                            </div>
                        </div>
                    </div>
                    <Button label="Ajouter une nouvelle catégorie" icon="pi pi-plus" class="p-button-outlined p-button-indigo w-full border-2 border-dashed font-black" />
                </div>
                <template #footer>
                    <Button label="Fermer" @click="displayCategoryDialog = false" class="p-button-text p-button-secondary font-bold" />
                    <Button label="Sauvegarder l'ordre" icon="pi pi-save" class="p-button-primary" />
                </template>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Social & Community Engine v2.0.
            </footer>
        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-forum-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 1.5rem;
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

:deep(.p-paginator) {
    border: none;
    padding: 2rem;
    background: transparent;
}

:deep(.p-tabview-nav) {
    border-bottom: 2px solid #F1F5F9;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #94A3B8;
    padding: 1.25rem 2rem;
    transition: all 0.3s;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1 !important;
    border-bottom: 2px solid #6366F1 !important;
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Categories Dynamiques - Utilisation forcée des couleurs Tailwind */
.text-indigo-500 { color: #6366f1; }
.bg-indigo-50 { background-color: #f5f3ff; }
.bg-indigo-100 { background-color: #e0e7ff; }
.text-emerald-500 { color: #10b981; }
.bg-emerald-50 { background-color: #ecfdf5; }
.bg-emerald-100 { background-color: #d1fae5; }
.text-rose-500 { color: #f43f5e; }
.bg-rose-50 { background-color: #fff1f2; }
.bg-rose-100 { background-color: #ffe4e6; }
.text-amber-500 { color: #f59e0b; }
.bg-amber-50 { background-color: #fffbeb; }
.bg-amber-100 { background-color: #fef3c7; }

/* Custom ScrollPanel */
:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
    opacity: 1;
}

/* Animations */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
