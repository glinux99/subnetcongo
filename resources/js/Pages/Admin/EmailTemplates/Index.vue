<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { ref, computed } from 'vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    grouped: { type: Object, default: () => ({}) },
});

const search = ref('');
const selectedCategory = ref('');

const categories = [
    { label: 'Tous', value: '' },
    { label: 'Certificats', value: 'certificate' },
    { label: 'Inscriptions', value: 'enrollment' },
    { label: 'Cours', value: 'course' },
    { label: 'Examens', value: 'exam' },
    { label: 'Bienvenue', value: 'welcome' },
    { label: 'Support', value: 'support' },
    { label: 'Marketing', value: 'marketing' },
];

const getCategoryLabel = (cat) => categories.find(c => c.value === cat)?.label || cat;

const getCategoryIcon = (cat) => {
    const icons = {
        certificate: '🎓',
        enrollment: '✅',
        course: '📚',
        exam: '📝',
        welcome: '👋',
        support: '🎫',
        marketing: '📧',
    };
    return icons[cat] || '📧';
};

const filteredGrouped = computed(() => {
    const result = {};
    const s = search.value.toLowerCase().trim();

    for (const [category, tmpls] of Object.entries(props.grouped || {})) {
        if (selectedCategory.value && selectedCategory.value !== category) {
            continue;
        }

        const filteredTmpls = (tmpls || []).filter(t => {
            if (!s) return true;

            const matchName = t.name?.toLowerCase().includes(s);
            const matchSubject = t.subject?.toLowerCase().includes(s);
            const matchKey = t.key?.toLowerCase().includes(s);

            return matchName || matchSubject || matchKey;
        });

        if (filteredTmpls.length > 0) {
            result[category] = filteredTmpls;
        }
    }

    return result;
});

const hasResults = computed(() => Object.keys(filteredGrouped.value).length > 0);

const getEventLabel = (event) => {
    const labels = {
        'certificate_generated': 'Certificat généré',
        'enrollment_confirmed': 'Inscription confirmée',
        'course_added': 'Cours ajouté',
        'exam_passed': 'Examen réussi',
        'exam_failed': 'Examen échoué',
        'welcome': 'Bienvenue',
        'password_reset': 'Réinitialisation mot de passe',
    };
    return labels[event] || event || 'Événement manuel';
};
</script>

<template>
    <Head title="Modèles d'emails" />
    <AppLayout>
        <div class="min-h-screen bg-[#f1f5f9] pb-20 font-sans">
            
            <!-- HEADER (Style uniforme avec Nouveau Message) -->
            <div class="bg-[#11131f] pt-12 pb-24 px-6 lg:px-12 relative overflow-hidden">
                <div class="max-w-[1500px] mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    
                    <!-- Titre & Sous-titre -->
                    <div>
                        <div class="mb-3">
                            <span class="border border-red-500/30 bg-red-500/10 text-red-500 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wider">
                                SYSTÈME
                            </span>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase italic flex flex-wrap items-center gap-x-3">
                            MODÈLES <span class="text-[#ef4444]">D'EMAILS</span>
                        </h1>
                        <p class="text-slate-400 mt-3 text-sm italic max-w-xl">
                            Configurez et gérez les messages automatiques envoyés à vos utilisateurs selon les différents événements de la plateforme.
                        </p>
                    </div>

                    <!-- Statistiques rapides (Optionnel, ajoute du cachet) -->
                    <div class="flex gap-4">
                        <div class="bg-white/5 border border-white/10 rounded-xl p-4 min-w-[120px]">
                            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Modèles</div>
                            <div class="text-2xl font-black text-white">{{ templates?.length || 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1500px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                
                <!-- Barre d'outils / Filtres (Épurée) -->
                <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-200 p-2.5 mb-10 flex flex-col md:flex-row gap-3 items-center">
                    <div class="relative w-full flex-1">
                        <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                        <InputText
                            v-model="search"
                            placeholder="Rechercher un modèle (nom, clé, sujet)..."
                            class="!rounded-lg w-full !pl-11 !py-3 !border-none !bg-slate-50 focus:!bg-blue-50/50 transition-colors placeholder:text-slate-400 font-medium"
                        />
                    </div>
                    <div class="w-full md:w-[280px]">
                        <Select
                            v-model="selectedCategory"
                            :options="categories"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Toutes les catégories"
                            class="w-full !rounded-lg !border-none !bg-slate-50 !py-1 font-medium shadow-none"
                        />
                    </div>
                </div>

                <!-- Liste des modèles groupés -->
                <div v-for="(templatesGroup, category) in filteredGrouped" :key="category" class="mb-12">
                    
                    <!-- Titre de catégorie stylisé -->
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-slate-200 flex items-center justify-center text-xl">
                            {{ getCategoryIcon(category) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-800 tracking-tight">
                                {{ getCategoryLabel(category) }}
                            </h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                                {{ templatesGroup.length }} modèle{{ templatesGroup.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="flex-1 h-px bg-slate-200 ml-4"></div>
                    </div>

                    <!-- Grille de cartes -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div
                            v-for="template in templatesGroup"
                            :key="template.id"
                            class="bg-white rounded-xl border border-slate-200 p-6 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full relative overflow-hidden"
                        >
                            <!-- Bande de couleur sur le côté si inactif -->
                            <div v-if="!template.is_active" class="absolute left-0 top-0 bottom-0 w-1 bg-slate-300"></div>
                            <div v-else class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>

                            <div class="flex items-start justify-between mb-4">
                                <div class="min-w-0 pr-4">
                                    <h3 class="font-bold text-lg text-slate-900 group-hover:text-blue-600 transition-colors truncate">
                                        {{ template.name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-slate-400 font-mono bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                            {{ template.key }}
                                        </span>
                                    </div>
                                </div>
                                <Tag
                                    :value="template.is_active ? 'Actif' : 'Inactif'"
                                    :severity="template.is_active ? 'success' : 'secondary'"
                                    class="!text-[10px] !font-bold !px-2 !py-0.5 flex-shrink-0 !rounded-md"
                                />
                            </div>

                            <div class="bg-slate-50 rounded-lg p-3 mb-5 flex-1 border border-slate-100">
                                <p class="text-sm font-medium text-slate-700 line-clamp-2">
                                    <span class="text-slate-400 font-normal mr-1">Sujet :</span>
                                    {{ template.subject || '(Aucun objet défini)' }}
                                </p>
                            </div>

                            <!-- Variables -->
                            <div class="mb-5">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Variables disponibles</p>
                                <div v-if="template.variables && template.variables.length" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="variable in template.variables.slice(0, 3)"
                                        :key="variable"
                                        class="text-[10px] px-2 py-1 bg-blue-50 text-blue-700 font-mono rounded-md border border-blue-100"
                                    >
                                        {{ variable }}
                                    </span>
                                    <span
                                        v-if="template.variables.length > 3"
                                        class="text-[10px] px-2 py-1 bg-slate-100 text-slate-600 font-bold rounded-md cursor-help border border-slate-200"
                                        v-tooltip.top="template.variables.slice(3).join(', ')"
                                    >
                                        +{{ template.variables.length - 3 }}
                                    </span>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic">Aucune variable</div>
                            </div>

                            <!-- Footer Carte -->
                            <div class="flex items-center justify-between pt-4 border-t border-slate-100 mt-auto">
                                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                                    <i class="pi pi-bolt text-red-500"></i>
                                    {{ getEventLabel(template.trigger_event) }}
                                </div>
                                
                                <Link :href="route('admin.email-templates.show', template.id)">
                                    <button class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-500 hover:bg-blue-600 hover:text-white transition-colors duration-200" v-tooltip.top="'Modifier le modèle'">
                                        <i class="pi pi-pencil text-sm"></i>
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (Amélioré) -->
                <div v-if="!hasResults" class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-300">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-5 shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)]">
                        <i class="pi pi-search text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800">Aucun modèle trouvé</h3>
                    <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                        Nous n'avons trouvé aucun modèle correspondant à vos critères. Essayez de modifier votre recherche ou de changer de catégorie.
                    </p>
                    <Button
                        v-if="search || selectedCategory"
                        label="Réinitialiser les filtres"
                        icon="pi pi-refresh"
                        outlined
                        @click="search = ''; selectedCategory = ''"
                        class="mt-6 !rounded-xl"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>