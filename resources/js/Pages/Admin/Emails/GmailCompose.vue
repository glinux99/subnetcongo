<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
        <Head title="Nouveau message" />
        
        <div class="min-h-screen bg-slate-50">
            <!-- PageHeader -->
            <PageHeader
                :title="forwardedEmail ? 'Transférer le message' : 'Nouveau message'"
                subtitle="Composez et envoyez votre email"
                badge="Composition"
                icon="pi-pen-to-square"
                accent="blue"
                :actions="[
                    { label: 'Retour', icon: 'pi-arrow-left', onClick: goBack, variant: 'secondary' },
                    { label: 'Brouillon', icon: 'pi-save', onClick: saveDraft, variant: 'secondary', disabled: isSaving },
                    { label: 'Envoyer', icon: 'pi-send', onClick: sendEmail, variant: 'primary', disabled: !canSend || isSending }
                ]"
            />

            <!-- Compose form -->
            <div class="max-w-4xl mx-auto p-4">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- To field -->
                    <div class="flex items-center border-b border-slate-100 px-4 py-3">
                        <label class="text-sm font-medium text-slate-700 w-16">À :</label>
                        <InputText
                            v-model="to"
                            placeholder="Destinataires (séparés par des virgules)"
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
                            <label class="text-sm font-medium text-slate-700 w-16">Cc :</label>
                            <InputText
                                v-model="cc"
                                placeholder="Destinataires en copie"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                        <div class="flex items-center px-4 py-3">
                            <label class="text-sm font-medium text-slate-700 w-16">Cci :</label>
                            <InputText
                                v-model="bcc"
                                placeholder="Destinataires en copie cachée"
                                class="flex-1 border-0 focus:ring-0"
                            />
                        </div>
                    </div>

                    <!-- Subject field -->
                    <div class="flex items-center border-b border-slate-100 px-4 py-3">
                        <label class="text-sm font-medium text-slate-700 w-16">Objet :</label>
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
                    <div class="border-t border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-paperclip"
                                    label="Joindre un fichier"
                                    class="text-slate-600 hover:bg-slate-100"
                                    @click="showAttachments = !showAttachments"
                                    text
                                />
                                <div v-if="attachments.length > 0" class="flex items-center gap-2">
                                    <span class="text-sm text-slate-600">
                                        {{ attachments.length }} fichier(s) joint(s)
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-eraser"
                                    label="Effacer"
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
                            label="Envoyer"
                            icon="pi pi-send"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            @click="sendEmail"
                            :loading="isSending"
                            :disabled="!canSend"
                        />
                        <Button
                            label="Brouillon"
                            icon="pi pi-save"
                            class="text-slate-600 hover:bg-slate-100"
                            @click="saveDraft"
                            :loading="isSaving"
                            text
                        />
                    </div>
                    <div class="text-sm text-slate-500">
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
