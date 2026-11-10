<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { ref, computed } from 'vue';

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
        onSuccess: () => {
            router.visit(route('admin.emails.index'));
        },
        onFinish: () => isSending.value = false
    });
};

const saveDraft = () => {
    isSaving.value = true;
    router.post(route('admin.emails.saveDraft'), {
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
        <Head title="Nouveau message" />
        
        <div class="gmail-compose-container min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Button
                            icon="pi pi-arrow-left"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-full"
                            @click="goBack"
                            text
                            rounded
                        />
                        <h1 class="text-lg font-medium text-gray-800">
                            {{ forwardedEmail ? 'Transférer le message' : 'Nouveau message' }}
                        </h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            label="Brouillon"
                            icon="pi pi-save"
                            class="text-gray-600 hover:bg-gray-100"
                            @click="saveDraft"
                            :loading="isSaving"
                            text
                        />
                        <Button
                            label="Envoyer"
                            icon="pi pi-send"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            @click="sendEmail"
                            :loading="isSending"
                            :disabled="!canSend"
                        />
                    </div>
                </div>
            </div>

            <!-- Compose form -->
            <div class="max-w-4xl mx-auto p-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- To field -->
                    <div class="flex items-center border-b border-gray-100 px-4 py-3">
                        <label class="text-sm font-medium text-gray-700 w-16">À :</label>
                        <InputText
                            v-model="to"
                            placeholder="Destinataires (séparés par des virgules)"
                            class="flex-1 border-0 focus:ring-0"
                        />
                        <Button
                            icon="pi pi-plus"
                            class="p-1 text-gray-400 hover:text-gray-600"
                            @click="showCcBcc = !showCcBcc"
                            text
                            rounded
                        />
                    </div>

                    <!-- CC/BCC fields -->
                    <div v-if="showCcBcc" class="border-b border-gray-100">
                        <div class="flex items-center px-4 py-3">
                            <label class="text-sm font-medium text-gray-700 w-16">Cc :</label>
                            <InputText
                                v-model="cc"
                                placeholder="Destinataires en copie"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                        <div class="flex items-center px-4 py-3">
                            <label class="text-sm font-medium text-gray-700 w-16">Cci :</label>
                            <InputText
                                v-model="bcc"
                                placeholder="Destinataires en copie cachée"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                    </div>

                    <!-- Subject field -->
                    <div class="flex items-center border-b border-gray-100 px-4 py-3">
                        <label class="text-sm font-medium text-gray-700 w-16">Objet :</label>
                        <InputText
                            v-model="subject"
                            placeholder="Objet du message"
                            class="flex-1 border-0 focus:ring-0 font-medium"
                        />
                    </div>

                    <!-- Body editor -->
                    <div class="p-4">
                        <Textarea
                            v-model="body"
                            placeholder="Écrivez votre message..."
                            rows="15"
                            class="w-full border-0 focus:ring-0 resize-none"
                        />
                    </div>

                    <!-- Attachments -->
                    <div class="border-t border-gray-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-paperclip"
                                    label="Joindre un fichier"
                                    class="text-gray-600 hover:bg-gray-100"
                                    @click="showAttachments = !showAttachments"
                                    text
                                />
                                <div v-if="attachments.length > 0" class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600">
                                        {{ attachments.length }} fichier(s) joint(s)
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-eraser"
                                    label="Effacer"
                                    class="text-gray-400 hover:text-gray-600 text-sm"
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
                                class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg"
                            >
                                <i class="pi pi-file text-gray-400"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                                </div>
                                <Button
                                    icon="pi pi-times"
                                    class="p-1 text-gray-400 hover:text-red-600"
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
                            icon="pi pi-send"
                            label="Envoyer"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            @click="sendEmail"
                            :loading="isSending"
                            :disabled="!canSend"
                        />
                        <Button
                            icon="pi pi-save"
                            label="Brouillon"
                            class="text-gray-600 hover:bg-gray-100"
                            @click="saveDraft"
                            :loading="isSaving"
                            text
                        />
                    </div>
                    <div class="text-sm text-gray-500">
                        <span v-if="!canSend">Remplissez tous les champs pour envoyer</span>
                        <span v-else class="text-green-600">Prêt à envoyer</span>
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
