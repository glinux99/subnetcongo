<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import { ref } from 'vue';

const props = defineProps({
    template: { type: Object, required: true },
});

const form = useForm({
    subject: props.template.subject,
    content: props.template.content,
    is_active: props.template.is_active,
});

const testEmail = ref('');
const showTestForm = ref(false);
const copiedVariable = ref(null); // Pour l'effet UX de copie

const submit = () => {
    form.put(route('admin.email-templates.update', props.template.id), {
        preserveScroll: true,
    });
};

const sendTest = () => {
    if (!testEmail.value) return;

    router.post(route('admin.email-templates.test'), {
        template_id: props.template.id,
        email: testEmail.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showTestForm.value = false;
            testEmail.value = '';
        },
    });
};

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

// Amélioration UX de la copie
const copyVariable = (variable) => {
    const textToCopy = `{{${variable}}}`;
    navigator.clipboard.writeText(textToCopy);

    // Animation de confirmation
    copiedVariable.value = variable;
    setTimeout(() => {
        copiedVariable.value = null;
    }, 1500);
};
</script>

<template>
    <Head :title="`Modifier - ${template.name}`" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header modernisé -->
            <div class="bg-[#0f172a] pt-8 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[100px]"></div>
                <div class="max-w-[1200px] mx-auto relative z-10">
                    <Link :href="route('admin.email-templates.index')" class="inline-flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors bg-slate-800/50 px-4 py-2 rounded-full backdrop-blur-sm">
                        <i class="pi pi-arrow-left text-xs"></i> Retour aux modèles
                    </Link>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-3xl border border-white/10 shadow-inner backdrop-blur-sm">
                                {{ getCategoryIcon(template.category) }}
                            </div>
                            <div>
                                <h1 class="text-2xl font-black text-white tracking-tight">{{ template.name }}</h1>
                                <p class="text-slate-400 text-sm mt-1 font-mono bg-slate-800/50 inline-block px-2 py-0.5 rounded">{{ template.key }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Tag
                                :value="form.is_active ? 'Modèle Actif' : 'Modèle Inactif'"
                                :class="[
                                    '!px-4 !py-2 !rounded-full !font-bold transition-colors duration-300',
                                    form.is_active ? '!bg-blue-500/20 !text-blue-400 !border !border-blue-500/30' : '!bg-slate-700/50 !text-slate-400 !border !border-slate-600'
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1200px] mx-auto px-4 lg:px-6 -mt-12 relative z-20">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Formulaire -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                            <form @submit.prevent="submit" class="flex flex-col h-full">

                                <div class="p-6 md:p-8 space-y-6">
                                    <!-- Active Toggle (Design Repensé) -->
                                    <div :class="['flex items-center justify-between p-5 rounded-2xl border transition-colors', form.is_active ? 'bg-blue-50 border-blue-100' : 'bg-slate-50 border-slate-200']">
                                        <div>
                                            <p :class="['font-bold text-base', form.is_active ? 'text-blue-900' : 'text-slate-700']">Activation automatique</p>
                                            <p :class="['text-sm mt-0.5', form.is_active ? 'text-blue-600' : 'text-slate-500']">
                                                {{ form.is_active ? 'Ce modèle sera utilisé par le système.' : 'Ce message est désactivé et ne sera pas envoyé.' }}
                                            </p>
                                        </div>
                                        <InputSwitch v-model="form.is_active" class="scale-110" />
                                    </div>

                                    <!-- Subject -->
                                    <div>
                                        <label class="block text-sm font-black text-slate-700 mb-2">Sujet de l'email</label>
                                        <div class="relative">
                                            <i class="pi pi-pencil absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <InputText
                                                v-model="form.subject"
                                                class="!rounded-xl w-full !pl-11 !py-3 !text-slate-800 !border-slate-200 focus:!border-blue-500"
                                                placeholder="Saisissez l'objet du message..."
                                            />
                                        </div>
                                        <p v-if="form.errors.subject" class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="pi pi-exclamation-circle"></i> {{ form.errors.subject }}</p>
                                    </div>

                                    <!-- Content (Simili-éditeur de code) -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-black text-slate-700">Contenu HTML</label>
                                            <span class="text-xs text-slate-400 bg-slate-100 px-2 py-1 rounded-md font-mono">HTML Supporté</span>
                                        </div>
                                        <div class="rounded-xl overflow-hidden border border-slate-800 bg-[#0f172a] shadow-inner focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
                                            <!-- Faux header type mac/éditeur -->
                                            <div class="bg-[#1e293b] px-4 py-2 flex items-center gap-2 border-b border-slate-700/50">
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                                <span class="ml-2 text-xs text-slate-400 font-mono tracking-widest uppercase">template.html</span>
                                            </div>
                                            <!-- CORRECTION ICI: Ajout de !text-slate-100 pour forcer le texte en blanc -->
                                            <Textarea
                                                v-model="form.content"
                                                rows="18"
                                                class="w-full !border-none !shadow-none !bg-transparent !text-slate-100 font-mono text-[14px] leading-relaxed p-4 focus:!ring-0 resize-y"
                                                placeholder="<div>Entrez votre HTML ici...</div>"
                                            />
                                        </div>
                                        <p v-if="form.errors.content" class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="pi pi-exclamation-circle"></i> {{ form.errors.content }}</p>
                                    </div>
                                </div>

                                <!-- Actions du formulaire (Footer) -->
                                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-b-3xl mt-auto">
                                    <Button
                                        type="button"
                                        :label="showTestForm ? 'Fermer le test' : 'Tester l\'envoi'"
                                        :icon="showTestForm ? 'pi pi-times' : 'pi pi-send'"
                                        :class="['!rounded-xl transition-all', showTestForm ? '!text-slate-500 hover:!bg-slate-200' : '!text-blue-600 hover:!bg-blue-50 !border-blue-200']"
                                        outlined
                                        @click="showTestForm = !showTestForm"
                                    />
                                    <Button
                                        type="submit"
                                        label="Enregistrer les modifications"
                                        icon="pi pi-save"
                                        :loading="form.processing"
                                        class="!rounded-xl !bg-slate-800 hover:!bg-slate-900 !border-none px-6 shadow-lg shadow-slate-200 w-full sm:w-auto"
                                    />
                                </div>
                            </form>
                        </div>

                        <!-- Formulaire de Test (Rendu conditionnel doux avec Animation) -->
                        <div v-show="showTestForm" class="bg-blue-50 rounded-3xl p-6 border border-blue-100 transform transition-all duration-300 animate-fadein">
                            <h3 class="text-sm font-black uppercase tracking-widest text-blue-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-paper-plane text-blue-600"></i> Envoyer un test
                            </h3>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="relative flex-1">
                                    <i class="pi pi-at absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <InputText
                                        v-model="testEmail"
                                        type="email"
                                        placeholder="votre-email@domaine.com"
                                        class="w-full !rounded-xl !pl-11 border-white"
                                        @keyup.enter="sendTest"
                                    />
                                </div>
                                <Button
                                    label="Envoyer"
                                    @click="sendTest"
                                    :disabled="!testEmail"
                                    class="!rounded-xl !bg-blue-600 hover:!bg-blue-700 !border-none shadow-md"
                                />
                            </div>
                            <p class="text-xs text-blue-600/70 mt-3">L'email sera envoyé avec les données actuelles (pensez à sauvegarder avant de tester si vous avez fait des modifications).</p>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">

                        <!-- Informations de base -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-5 flex items-center gap-2">
                                <i class="pi pi-info-circle text-slate-400"></i> Métadonnées
                            </h3>
                            <div class="space-y-4">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Déclencheur</p>
                                    <p class="font-semibold text-slate-800 flex items-center gap-2">
                                        <i class="pi pi-bolt text-red-500"></i> {{ template.trigger_event }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Catégorie</p>
                                    <p class="font-semibold text-slate-800 capitalize">{{ template.category }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Variables (Améliorées UX) -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 flex items-center gap-2">
                                    <i class="pi pi-code text-slate-400"></i> Variables
                                </h3>
                                <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded-full font-bold">{{ template.variables?.length || 0 }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mb-4 leading-relaxed">Cliquez sur une variable pour la copier et collez-la dans le sujet ou le contenu.</p>

                            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                <button
                                    v-for="variable in template.variables"
                                    :key="variable"
                                    @click="copyVariable(variable)"
                                    type="button"
                                    :class="[
                                        'w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm transition-all duration-200 border',
                                        copiedVariable === variable
                                            ? 'bg-blue-50 border-blue-200'
                                            : 'bg-white border-slate-100 hover:border-blue-200 hover:shadow-sm'
                                    ]"
                                >
                                    <!-- CORRECTION ICI: Affichage des accolades {{ }} autour de la variable -->
                                    <code :class="['font-mono font-bold text-xs', copiedVariable === variable ? 'text-blue-600' : 'text-blue-600']">
                                        {{ variable }}
                                    </code>
                                    <i :class="['pi text-xs', copiedVariable === variable ? 'pi-check text-blue-500' : 'pi-copy text-slate-300']"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Astuces HTML -->
                        <div class="bg-gradient-to-br from-blue-50 to-purple-50 border border-blue-100 rounded-3xl p-6">
                            <h3 class="text-sm font-black text-blue-900 mb-3 flex items-center gap-2">
                                💡 Astuces d'édition
                            </h3>
                            <ul class="text-xs text-blue-800/80 space-y-2.5">
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check-circle mt-0.5 text-blue-400"></i>
                                    <span>Les variables sont dynamiques et se remplacent lors de l'envoi.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check-circle mt-0.5 text-blue-400"></i>
                                    <span>Utilisez des balises <code>&lt;br&gt;</code> pour les sauts de ligne si vous n'utilisez pas de paragraphes.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check-circle mt-0.5 text-blue-400"></i>
                                    <span>Évitez le CSS complexe, privilégiez le CSS en ligne (style="...").</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation d'apparition douce pour le formulaire de test */
@keyframes fadein {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadein {
    animation: fadein 0.3s ease-out forwards;
}

/* Scrollbar personnalisée pour la liste des variables */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Force la suppression de la bordure PrimeVue sur le Textarea */
:deep(.p-inputtextarea) {
    box-shadow: none !important;
}
</style>
