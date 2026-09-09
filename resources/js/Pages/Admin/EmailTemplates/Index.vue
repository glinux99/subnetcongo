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

// Utilisation d'un 'computed' pour filtrer le tableau GROUPÉ directement
const filteredGrouped = computed(() => {
    const result = {};
    const s = search.value.toLowerCase().trim();

    // On sécurise le parcours au cas où props.grouped serait null
    for (const [category, tmpls] of Object.entries(props.grouped || {})) {

        // 1. Filtre par catégorie
        if (selectedCategory.value && selectedCategory.value !== category) {
            continue;
        }

        // 2. Filtre par recherche texte (sécurisé contre les valeurs nulles)
        const filteredTmpls = (tmpls || []).filter(t => {
            if (!s) return true;

            // L'opérateur '?.' empêche l'erreur si le champ est null
            const matchName = t.name?.toLowerCase().includes(s);
            const matchSubject = t.subject?.toLowerCase().includes(s);
            const matchKey = t.key?.toLowerCase().includes(s);

            return matchName || matchSubject || matchKey;
        });

        // On n'ajoute la catégorie au résultat final que s'il reste des modèles dedans
        if (filteredTmpls.length > 0) {
            result[category] = filteredTmpls;
        }
    }

    return result;
});

// Vérifie rapidement s'il y a au moins un résultat pour l'affichage du "Empty State"
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
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header -->
            <div class="bg-[#0f172a] pt-10 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-rose-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[1500px] mx-auto relative z-10 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                            <span>📧</span> Modèles d'emails
                        </h1>
                        <p class="text-slate-400 text-sm mt-1">Configurez les messages automatiques envoyés aux utilisateurs</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1500px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <!-- Remplacement du wrapper classique par une icône absolute plus stable avec Tailwind -->
                            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                            <InputText
                                v-model="search"
                                placeholder="Rechercher un modèle..."
                                class="!rounded-xl w-full !pl-10 relative"
                            />
                        </div>
                        <Select
                            v-model="selectedCategory"
                            :options="categories"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Catégorie"
                            class="!rounded-xl min-w-[200px]"
                        />
                    </div>
                </div>

                <!-- Templates by Category -->
                <!-- On itère maintenant sur "filteredGrouped" au lieu de "grouped" -->
                <div v-for="(templates, category) in filteredGrouped" :key="category" class="mb-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 mb-4 flex items-center gap-2">
                            <span class="text-2xl">{{ getCategoryIcon(category) }}</span>
                            {{ getCategoryLabel(category) }}
                            <span class="text-sm font-normal text-slate-400">({{ templates.length }})</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="template in templates"
                                :key="template.id"
                                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition group flex flex-col h-full"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div class="min-w-0 pr-3">
                                        <h3 class="font-bold text-slate-900 group-hover:text-rose-600 transition truncate">
                                            {{ template.name }}
                                        </h3>
                                        <p class="text-xs text-slate-500 font-mono mt-1 truncate">{{ template.key }}</p>
                                    </div>
                                    <Tag
                                        :value="template.is_active ? 'Actif' : 'Inactif'"
                                        :severity="template.is_active ? 'success' : 'secondary'"
                                        class="!text-[10px] flex-shrink-0"
                                    />
                                </div>

                                <p class="text-sm text-slate-600 mb-4 line-clamp-2 flex-1">
                                    {{ template.subject || '(Sans objet)' }}
                                </p>

                                <!-- Variables -->
                                <div v-if="template.variables && template.variables.length" class="flex flex-wrap gap-1 mb-4">
                                    <span
                                        v-for="variable in template.variables.slice(0, 3)"
                                        :key="variable"
                                        class="text-[10px] px-2 py-1 bg-slate-100 text-slate-600 rounded"
                                    >
                                        {{   variable  }}
                                    </span>
                                    <span
                                        v-if="template.variables.length > 3"
                                        class="text-[10px] px-2 py-1 bg-slate-100 text-slate-500 rounded"
                                        v-tooltip.top="template.variables.slice(3).join(', ')"
                                    >
                                        +{{ template.variables.length - 3 }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
                                    <span class="text-xs text-slate-400 bg-slate-50 px-2 py-1 rounded-md">
                                        <i class="pi pi-bolt text-[10px] mr-1"></i>
                                        {{ getEventLabel(template.trigger_event) }}
                                    </span>
                                    <Link :href="route('admin.email-templates.show', template.id)">
                                        <Button
                                            label="Modifier"
                                            icon="pi pi-pencil"
                                            size="small"
                                            text
                                            class="!rounded-xl hover:bg-slate-50"
                                        />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State corrigé -->
                <div v-if="!hasResults" class="text-center py-16">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 shadow-inner">
                        <i class="pi pi-search text-3xl text-slate-300"></i>
                    </div>
                    <p class="text-slate-600 text-lg font-bold">Aucun modèle trouvé</p>
                    <p class="text-sm text-slate-400 mt-1">Modifiez vos critères de recherche ou de catégorie.</p>
                    <Button
                        v-if="search || selectedCategory"
                        label="Effacer les filtres"
                        text
                        @click="search = ''; selectedCategory = ''"
                        class="mt-4"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
