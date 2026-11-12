<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    prefill: { type: Object, default: () => ({}) },
    forwardedEmail: { type: Object, default: null },
});

const to = ref(props.prefill.to || '');
const cc = ref('');
const bcc = ref('');
const subject = ref(props.prefill.subject || '');
const body = ref(props.prefill.body || '');
const attachments = ref([]);
const showCcBcc = ref(false);
const isSending = ref(false);
const isSaving = ref(false);
const showAttachments = ref(false);

const canSend = computed(() => {
    return to.value.trim() && subject.value.trim() && body.value.trim();
});

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const addAttachment = (event) => {
    const files = Array.from(event.target.files);
    attachments.value.push(...files);
};

const removeAttachment = (index) => {
    attachments.value.splice(index, 1);
};

const sendEmail = () => {
    if (!canSend.value) return;
    
    isSending.value = true;
    const formData = new FormData();
    formData.append('to', to.value);
    formData.append('subject', subject.value);
    formData.append('body', body.value);
    
    if (cc.value) formData.append('cc', cc.value);
    if (bcc.value) formData.append('bcc', bcc.value);
    
    attachments.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
    });
    
    router.post(route('admin.emails.send'), formData, {
        preserveScroll: true,
        onFinish: () => isSending.value = false
    });
};

const saveDraft = () => {
    isSaving.value = true;
    router.post(route('admin.emails.draft'), {
        to: to.value,
        cc: cc.value,
        bcc: bcc.value,
        subject: subject.value,
        body: body.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Show success message
        },
        onFinish: () => isSaving.value = false
    });
};

const goBack = () => {
    router.visit(route('admin.emails.index'));
};

const insertTemplate = (template) => {
    body.value += template;
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_emails_gmail.page_title')" />
        
        <div class="min-h-screen bg-slate-50">
            <!-- PageHeader -->
            <PageHeader
                :title="forwardedEmail ? t('pages.admin_emails_gmail.title_forward') : t('pages.admin_emails_gmail.title_new')"
                :subtitle="t('pages.admin_emails_gmail.subtitle')"
                :badge="t('pages.admin_emails_gmail.badge')"
                icon="pi-pen-to-square"
                accent="blue"
                :actions="[
                    { label: t('pages.admin_emails_gmail.btn_back'), icon: 'pi-arrow-left', onClick: goBack, variant: 'secondary' },
                    { label: t('pages.admin_emails_gmail.btn_draft'), icon: 'pi-save', onClick: saveDraft, variant: 'secondary', disabled: isSaving },
                    { label: t('pages.admin_emails_gmail.btn_send'), icon: 'pi-send', onClick: sendEmail, variant: 'primary', disabled: !canSend || isSending }
                ]"
            />

            <!-- Compose form -->
            <div class="max-w-4xl mx-auto p-4">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- To field -->
                    <div class="flex items-center border-b border-slate-100 px-4 py-3">
                        <label class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_emails_gmail.label_to') }}</label>
                        <InputText
                            v-model="to"
                            :placeholder="t('pages.admin_emails_gmail.placeholder_to')"
                            class="flex-1 border-0 focus:ring-0"
                        />
                        <Button
                            icon="pi pi-plus"
                            class="p-1 text-slate-400 hover:text-slate-600"
                            @click="showCcBcc = !showCcBcc"
                            text
                            rounded
                        />
                    </div>

                    <!-- CC/BCC fields -->
                    <div v-if="showCcBcc" class="border-b border-slate-100">
                        <div class="flex items-center px-4 py-3">
                            <label class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_emails_gmail.label_cc') }}</label>
                            <InputText
                                v-model="cc"
                                :placeholder="t('pages.admin_emails_gmail.placeholder_cc')"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                        <div class="flex items-center px-4 py-3">
                            <label class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_emails_gmail.label_bcc') }}</label>
                            <InputText
                                v-model="bcc"
                                :placeholder="t('pages.admin_emails_gmail.placeholder_bcc')"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                    </div>

                    <!-- Subject field -->
                    <div class="flex items-center border-b border-slate-100 px-4 py-3">
                        <label class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_emails_gmail.label_subject') }}</label>
                        <InputText
                            v-model="subject"
                            :placeholder="t('pages.admin_emails_gmail.placeholder_subject')"
                            class="flex-1 border-0 focus:ring-0 font-medium"
                        />
                    </div>

                    <!-- Body editor -->
                    <div class="p-4">
                        <Textarea
                            v-model="body"
                            :placeholder="t('pages.admin_emails_gmail.placeholder_body')"
                            rows="15"
                            class="w-full border-0 focus:ring-0 resize-none"
                        />
                    </div>

                    <!-- Attachments -->
                    <div class="border-t border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-paperclip"
                                    :label="t('pages.admin_emails_gmail.btn_attach')"
                                    class="text-slate-600 hover:bg-slate-100"
                                    @click="showAttachments = !showAttachments"
                                    text
                                />
                                <div v-if="attachments.length > 0" class="flex items-center gap-2">
                                    <span class="text-sm text-slate-600">
                                        {{ t('pages.admin_emails_gmail.files_count', { count: attachments.length }) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-eraser"
                                    :label="t('pages.admin_emails_gmail.btn_clear')"
                                    class="text-slate-400 hover:text-slate-600 text-sm"
                                    @click="body = ''; to = ''; cc = ''; bcc = ''; subject = ''; attachments = []"
                                    text
                                />
                            </div>
                        </div>

                        <!-- Attachment list -->
                        <div v-if="attachments.length > 0" class="mt-3 space-y-2">
                            <div
                                v-for="(file, index) in attachments"
                                :key="index"
                                class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg"
                            >
                                <i class="pi pi-file text-slate-400"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ file.name }}</p>
                                    <p class="text-xs text-slate-500">{{ formatFileSize(file.size) }}</p>
                                </div>
                                <Button
                                    icon="pi pi-times"
                                    class="p-1 text-slate-400 hover:text-red-600"
                                    @click="removeAttachment(index)"
                                    text
                                    rounded
                                />
                            </div>
                        </div>

                        <!-- File input -->
                        <input
                            type="file"
                            multiple
                            @change="addAttachment"
                            class="hidden"
                            ref="fileInput"
                        />
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Button
                            :label="t('pages.admin_emails_gmail.btn_send')"
                            icon="pi pi-send"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            @click="sendEmail"
                            :loading="isSending"
                            :disabled="!canSend"
                        />
                        <Button
                            :label="t('pages.admin_emails_gmail.btn_draft')"
                            icon="pi pi-save"
                            class="text-slate-600 hover:bg-slate-100"
                            @click="saveDraft"
                            :loading="isSaving"
                            text
                        />
                    </div>
                    <div class="text-sm text-slate-500">
                        <span v-if="!canSend">{{ t('pages.admin_emails_gmail.hint_fill') }}</span>
                        <span v-else class="text-green-600">{{ t('pages.admin_emails_gmail.hint_ready') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.gmail-compose-container {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
}
</style>
