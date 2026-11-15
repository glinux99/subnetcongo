<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { useAppVersion } from '@/composables/useAppVersion';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { label: appVersion } = useAppVersion();
const toast = useToast();
const confirmAction = useConfirmAction();

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
const fileInput = ref(null);

const templates = computed(() => [
    { label: t('pages.admin_emails_compose.tpl1_label'), subject: t('pages.admin_emails_compose.tpl1_subject'), body: t('pages.admin_emails_compose.tpl1_body') },
    { label: t('pages.admin_emails_compose.tpl2_label'), subject: t('pages.admin_emails_compose.tpl2_subject'), body: t('pages.admin_emails_compose.tpl2_body') },
    { label: t('pages.admin_emails_compose.tpl3_label'), subject: t('pages.admin_emails_compose.tpl3_subject'), body: t('pages.admin_emails_compose.tpl3_body') },
    { label: t('pages.admin_emails_compose.tpl4_label'), subject: t('pages.admin_emails_compose.tpl4_subject'), body: t('pages.admin_emails_compose.tpl4_body') },
]);

const form = useForm({
    to: props.prefill.to || '',
    cc: '',
    bcc: '',
    subject: props.prefill.subject || '',
    body: props.prefill.body || '',
    attachments: []
});

const applyTemplate = (template) => {
    if (!form.subject && template.subject) form.subject = template.subject;
    if (!form.body) form.body = template.body;
    showTemplateMenu.value = false;
};

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    form.attachments = [...form.attachments, ...files];
};

const removeAttachment = (index) => {
    form.attachments.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
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
        onSuccess: () => toast.add({ severity: 'success', summary: t('pages.admin_emails_compose.toast_draft_saved'), life: 2500 }),
    });
};

const discard = () => {
    confirmAction({
        header: t('pages.admin_emails_compose.confirm_discard_header'),
        message: t('pages.admin_emails_compose.confirm_discard_message'),
        acceptLabel: t('pages.admin_emails_compose.confirm_discard_accept'),
        rejectLabel: t('pages.admin_emails_compose.confirm_discard_reject'),
        danger: true,
        accept: () => router.visit(route('admin.emails.index')),
    });
};

const isValid = computed(() => form.to && form.subject && form.body);
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_emails_compose.page_title')" />
        <div class="min-h-screen bg-slate-50 pb-20 font-sans">

            <!-- PageHeader -->
            <PageHeader
                :title="`${t('pages.admin_emails_compose.hero_title_1')} ${t('pages.admin_emails_compose.hero_title_2')}`"
                :subtitle="t('pages.admin_emails_compose.hero_subtitle')"
                :badge="appVersion"
                icon="pi-pen-to-square"
                accent="blue"
            >
                <template #actions>
                    <Link :href="route('admin.emails.index')" class="btn-header-secondary">
                        <i class="pi pi-arrow-left"></i> {{ t('pages.admin_emails_compose.btn_back') }}
                    </Link>
                    <button type="button" class="btn-header-primary" @click="saveDraft">
                        <i class="pi pi-save"></i> {{ t('pages.admin_emails_compose.btn_draft') }}
                    </button>
                </template>
            </PageHeader>

            <!-- COMPOSER -->
            <div class="max-w-[1000px] mx-auto px-4 lg:px-6 pt-6 relative z-20">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden flex flex-col">
                    
                    <!-- Ligne "À" avec Cc/Cci intégrés -->
                    <div class="px-6 py-2 border-b border-slate-100 flex items-center focus-within:bg-blue-50/30 transition-colors">
                        <label class="text-slate-500 font-medium w-16 text-sm">{{ t('pages.admin_emails_compose.label_to') }}</label>
                        <input v-model="form.to" type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-1.5 px-0 text-slate-800 placeholder-slate-300" :placeholder="t('pages.admin_emails_compose.placeholder_to')">
                        
                        <!-- Toggle Cc / Cci -->
                        <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                            <button v-if="!showCc" @click="showCc = true" class="hover:text-slate-800 hover:underline">{{ t('pages.admin_emails_compose.label_cc') }}</button>
                            <button v-if="!showBcc" @click="showBcc = true" class="hover:text-slate-800 hover:underline">{{ t('pages.admin_emails_compose.label_bcc') }}</button>
                        </div>
                    </div>

                    <!-- Ligne "Cc" -->
                    <div v-if="showCc" class="px-6 py-2 border-b border-slate-100 flex items-center focus-within:bg-blue-50/30 transition-colors">
                        <label class="text-slate-500 font-medium w-16 text-sm">{{ t('pages.admin_emails_compose.label_cc') }}</label>
                        <input v-model="form.cc" type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-1.5 px-0 text-slate-800" :placeholder="t('pages.admin_emails_compose.placeholder_cc')">
                        <button @click="showCc = false; form.cc = ''" class="text-slate-400 hover:text-red-500"><i class="pi pi-times text-xs"></i></button>
                    </div>

                    <!-- Ligne "Cci" -->
                    <div v-if="showBcc" class="px-6 py-2 border-b border-slate-100 flex items-center focus-within:bg-blue-50/30 transition-colors">
                        <label class="text-slate-500 font-medium w-16 text-sm">{{ t('pages.admin_emails_compose.label_bcc') }}</label>
                        <input v-model="form.bcc" type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-1.5 px-0 text-slate-800" :placeholder="t('pages.admin_emails_compose.placeholder_bcc')">
                        <button @click="showBcc = false; form.bcc = ''" class="text-slate-400 hover:text-red-500"><i class="pi pi-times text-xs"></i></button>
                    </div>

                    <!-- Ligne "Objet" -->
                    <div class="px-6 py-2 border-b border-slate-100 flex items-center focus-within:bg-blue-50/30 transition-colors">
                        <input v-model="form.subject" type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-semibold py-1.5 px-0 text-slate-800 placeholder-slate-400" :placeholder="t('pages.admin_emails_compose.placeholder_subject')">
                    </div>

                    <!-- Zone de texte principale (Body) -->
                    <div class="flex-1 bg-white relative">
                        <textarea 
                            v-model="form.body" 
                            rows="15"
                            class="w-full h-full min-h-[350px] border-none focus:ring-0 bg-transparent p-6 text-sm text-slate-800 leading-relaxed font-sans resize-y placeholder-slate-300"
                            :placeholder="t('pages.admin_emails_compose.placeholder_body')"
                        ></textarea>
                    </div>

                    <!-- Zone d'affichage des pièces jointes -->
                    <div v-if="form.attachments.length > 0" class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex flex-wrap gap-2">
                        <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center gap-2 bg-white border border-slate-200 rounded-md px-3 py-1.5 shadow-sm">
                            <i class="pi pi-file text-slate-400 text-xs"></i>
                            <span class="text-xs font-medium text-slate-700 truncate max-w-[150px]">{{ file.name }}</span>
                            <span class="text-[10px] text-slate-400">({{ formatFileSize(file.size) }})</span>
                            <button @click="removeAttachment(index)" class="text-slate-400 hover:text-red-500 ml-1">
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Barre d'outils (Footer) -->
                    <div class="px-6 py-4 flex items-center justify-between bg-white border-t border-slate-100 rounded-b-xl">
                        
                        <!-- Actions principales (Envoyer & Outils) -->
                        <div class="flex items-center gap-4">
                            <!-- Bouton d'envoi style Gmail (Bleu) -->
                            <Button
                                :label="t('pages.admin_emails_compose.btn_send')"
                                @click="submit"
                                :loading="form.processing"
                                :disabled="!isValid"
                                class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-full !px-6 !py-2.5 !text-sm !font-bold shadow-md transition-transform active:scale-95"
                            />

                            <!-- Menu Modèles -->
                            <div class="relative">
                                <button @click="showTemplateMenu = !showTemplateMenu" class="w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 transition" :title="t('pages.admin_emails_compose.title_insert_template')">
                                    <i class="pi pi-clipboard"></i>
                                </button>
                                <div v-if="showTemplateMenu" class="absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-xl border border-slate-200 py-1 min-w-[250px] z-50">
                                    <div class="px-4 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 mb-1">{{ t('pages.admin_emails_compose.menu_templates') }}</div>
                                    <button v-for="tpl in templates" :key="tpl.label" @click="applyTemplate(tpl)" class="w-full text-left px-4 py-2.5 text-sm hover:bg-blue-50 text-slate-700 transition">
                                        {{ tpl.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Pièce jointe -->
                            <button @click="$refs.fileInput.click()" class="w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 transition" :title="t('pages.admin_emails_compose.title_attach')">
                                <i class="pi pi-paperclip"></i>
                            </button>
                            <input type="file" ref="fileInput" class="hidden" multiple @change="handleFileUpload">
                        </div>

                        <!-- Corbeille (Abandonner) -->
                        <div>
                            <button @click="discard" class="w-9 h-9 rounded-full hover:bg-red-50 hover:text-red-500 flex items-center justify-center text-slate-400 transition" :title="t('pages.admin_emails_compose.title_discard')">
                                <i class="pi pi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Erreurs éventuelles -->
                <div v-if="Object.keys(form.errors).length > 0" class="mt-4 bg-red-50 border border-red-200 text-red-600 rounded-lg p-4 text-sm">
                    <ul class="list-disc pl-5">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>