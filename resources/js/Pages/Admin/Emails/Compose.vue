<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Divider from 'primevue/divider';
import { ref, computed } from 'vue';

const props = defineProps({
    prefill: {
        type: Object,
        default: () => ({
            to: '',
            subject: '',
            body: '',
        }),
    },
});

const showCc = ref(false);
const showBcc = ref(false);
const showTemplateMenu = ref(false);

const templates = [
    { label: 'Réponse стандарт', subject: 'Re: ', body: '\n\n' },
    { label: 'Message de bienvenue', subject: 'Bienvenue sur SubnetCongo Academy', body: '\n\nBonjour,\n\nBienvenue sur SubnetCongo Academy !\n\nNous sommes ravis de vous compter parmi nos apprenants.\n\nCordialement,\nL\'équipe SubnetCongo' },
    { label: 'Suivi inscription', subject: 'Suivi de votre inscription', body: '\n\nBonjour,\n\nConcernant votre inscription, nous avons le plaisir de vous informer que...\n\nCordialement,\nL\'équipe SubnetCongo' },
    { label: 'Support technique', subject: 'Support technique - SubnetCongo', body: '\n\nBonjour,\n\nNous avons bien reçu votre demande de support.\n\nNotre équipe technique intervient dans les meilleurs délais.\n\nCordialement,\nL\'équipe Support SubnetCongo' },
];

const form = useForm({
    to: props.prefill.to || '',
    cc: '',
    bcc: '',
    subject: props.prefill.subject || '',
    body: props.prefill.body || '',
});

const applyTemplate = (template) => {
    if (!form.subject && template.subject) {
        form.subject = template.subject;
    }
    if (!form.body) {
        form.body = template.body;
    }
    showTemplateMenu.value = false;
};

const submit = () => {
    form.post(route('admin.emails.send'), {
        preserveScroll: true,
    });
};

const saveDraft = () => {
    router.post(route('admin.emails.draft'), {
        to: form.to,
        subject: form.subject,
        body: form.body,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Brouillon enregistré');
        },
    });
};

const discard = () => {
    if (confirm('Abandonner cet email ?')) {
        router.visit(route('admin.emails.index'));
    }
};

const charCount = computed(() => form.body.length);
const isValid = computed(() => {
    return form.to && form.subject && form.body;
});
</script>

<template>
    <Head title="Nouveau message" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header -->
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-rose-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[1000px] mx-auto relative z-10">
                    <Link :href="route('admin.emails.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4 transition">
                        <i class="pi pi-arrow-left"></i> Retour à la boîte de réception
                    </Link>
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-black text-white tracking-tight">
                            <i class="pi pi-pencil text-rose-400 mr-3"></i>Nouveau message
                        </h1>
                        <div class="flex items-center gap-2">
                            <Button
                                label="Brouillon"
                                icon="pi pi-save"
                                outlined
                                size="small"
                                @click="saveDraft"
                                class="!rounded-xl"
                            />
                            <Button
                                label="Envoyer"
                                icon="pi pi-send"
                                size="small"
                                :loading="form.processing"
                                :disabled="!isValid"
                                @click="submit"
                                class="!bg-emerald-600 hover:!bg-emerald-500 !border-none !rounded-xl"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1000px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <!-- Composer -->
                <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
                    <!-- Toolbar -->
                    <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100">
                        <div class="relative">
                            <Button
                                label="Modèle"
                                icon="pi pi-file"
                                outlined
                                size="small"
                                @click="showTemplateMenu = !showTemplateMenu"
                                class="!rounded-xl"
                            />
                            <div
                                v-if="showTemplateMenu"
                                class="absolute top-full left-0 mt-1 bg-white rounded-xl shadow-lg border border-slate-200 py-1 min-w-[220px] z-50"
                            >
                                <button
                                    v-for="tpl in templates"
                                    :key="tpl.label"
                                    @click="applyTemplate(tpl)"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 transition"
                                >
                                    {{ tpl.label }}
                                </button>
                            </div>
                        </div>
                        <Divider layout="vertical" />
                        <div class="text-xs text-slate-500">
                            {{ charCount }} caractères
                        </div>
                    </div>

                    <!-- Fields -->
                    <div class="p-6 space-y-4">
                        <!-- To -->
                        <div class="flex items-center gap-4">
                            <label class="w-16 text-sm font-black text-slate-500 flex-shrink-0">À</label>
                            <InputText
                                v-model="form.to"
                                type="email"
                                class="flex-1 !rounded-xl"
                                placeholder="destinataire@email.com"
                                :class="{ 'p-invalid': form.errors.to }"
                            />
                        </div>

                        <!-- CC Toggle -->
                        <div class="flex items-center gap-4">
                            <button
                                @click="showCc = !showCc"
                                class="w-16 text-sm text-slate-500 hover:text-rose-600 flex-shrink-0 text-left"
                            >
                                {{ showCc ? 'Cc' : '+ Cc' }}
                            </button>
                            <InputText
                                v-if="showCc"
                                v-model="form.cc"
                                type="email"
                                class="flex-1 !rounded-xl"
                                placeholder="copie@email.com"
                            />
                        </div>

                        <!-- BCC Toggle -->
                        <div class="flex items-center gap-4">
                            <button
                                @click="showBcc = !showBcc"
                                class="w-16 text-sm text-slate-500 hover:text-rose-600 flex-shrink-0 text-left"
                            >
                                {{ showBcc ? 'Bcc' : '+ Bcc' }}
                            </button>
                            <InputText
                                v-if="showBcc"
                                v-model="form.bcc"
                                type="email"
                                class="flex-1 !rounded-xl"
                                placeholder="copie cachée@email.com"
                            />
                        </div>

                        <!-- Subject -->
                        <div class="flex items-center gap-4">
                            <label class="w-16 text-sm font-black text-slate-500 flex-shrink-0">Sujet</label>
                            <InputText
                                v-model="form.subject"
                                class="flex-1 !rounded-xl"
                                placeholder="Objet du message"
                                :class="{ 'p-invalid': form.errors.subject }"
                            />
                        </div>

                        <p v-if="form.errors.to" class="text-rose-500 text-xs">{{ form.errors.to }}</p>
                        <p v-if="form.errors.subject" class="text-rose-500 text-xs">{{ form.errors.subject }}</p>

                        <Divider />

                        <!-- Body -->
                        <div>
                            <Textarea
                                v-model="form.body"
                                rows="18"
                                class="w-full !rounded-xl font-mono text-sm"
                                placeholder="Tapez votre message ici..."
                                :class="{ 'p-invalid': form.errors.body }"
                            />
                            <p v-if="form.errors.body" class="text-rose-500 text-xs mt-1">{{ form.errors.body }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100">
                        <Button
                            label="Abandonner"
                            icon="pi pi-times"
                            text
                            severity="danger"
                            @click="discard"
                            class="!rounded-xl"
                        />
                        <div class="flex items-center gap-3">
                            <Button
                                label="Brouillon"
                                icon="pi pi-save"
                                outlined
                                @click="saveDraft"
                                class="!rounded-xl"
                            />
                            <Button
                                label="Envoyer l'email"
                                icon="pi pi-send"
                                :loading="form.processing"
                                :disabled="!isValid"
                                @click="submit"
                                class="!bg-emerald-600 hover:!bg-emerald-500 !border-none !rounded-xl"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-4">
                    <h3 class="text-sm font-bold text-blue-800 mb-2">
                        <i class="pi pi-info-circle mr-2"></i>Conseils d'envoi
                    </h3>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• Utilisez le champ <strong>Cc</strong> pour envoyer une copie visible à d'autres destinataires</li>
                        <li>• Utilisez le champ <strong>Bcc</strong> pour envoyer une copie cachée (les autres destinataires ne verront pas ces adresses)</li>
                        <li>• Les modèles vous permettent de gagner du temps pour les réponses standards</li>
                        <li>• Vous pouvez enregistrer un brouillon à tout moment</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
