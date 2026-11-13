<script setup>
/**
 * MediaPicker — Sélecteur de média SubnetCongo.
 * Insérer une image ou une vidéo :
 *   - depuis l'appareil (téléphone/ordinateur, upload sur le disque media)
 *   - via un lien (URL directe, YouTube, Vimeo)
 *
 * Usage :
 *   <MediaPicker v-model:visible="open" accept="both" @insert="onInsert" />
 *
 * Événement insert : { type: 'image'|'video', url, name }
 * (les liens YouTube/Vimeo sont déjà convertis en URL d'embed)
 */
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import SncDialog from '@/Components/SncDialog.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import SelectButton from 'primevue/selectbutton';
import ProgressBar from 'primevue/progressbar';
import { useToast } from 'primevue/usetoast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    visible: { type: Boolean, default: false },
    accept: { type: String, default: 'both' }, // 'image' | 'video' | 'both'
});

const emit = defineEmits(['update:visible', 'insert']);

const mode = ref('device'); // 'device' | 'link'
const modeOptions = computed(() => {
    const opts = [{ label: t('components.media_picker.mode_device'), value: 'device' }];
    opts.push({ label: t('components.media_picker.mode_link'), value: 'link' });
    return opts;
});

const fileInput = ref(null);
const pickedFile = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploaded = ref(null); // { url, type, name }
const linkUrl = ref('');
const linkError = ref(false);

const fileAccept = computed(() => {
    if (props.accept === 'image') return 'image/*';
    if (props.accept === 'video') return 'video/*';
    return 'image/*,video/*';
});

watch(() => props.visible, (open) => {
    if (open) {
        pickedFile.value = null;
        uploaded.value = null;
        linkUrl.value = '';
        linkError.value = false;
        uploadProgress.value = 0;
        if (props.accept !== 'both') {
            mode.value = 'device';
        }
    }
});

const openFilePicker = () => {
    fileInput.value?.click();
};

const onFileChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    pickedFile.value = file;
    uploaded.value = null;
    event.target.value = '';
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / 1048576).toFixed(1) + ' Mo';
};

const isImageFile = (file) => file?.type?.startsWith('image/');
const pickedFileUrl = computed(() =>
    pickedFile.value ? URL.createObjectURL(pickedFile.value) : null
);

const uploadPicked = () => {
    if (!pickedFile.value || uploading.value) return;
    uploading.value = true;
    uploadProgress.value = 0;
    const fd = new FormData();
    fd.append('file', pickedFile.value);
    axios.post(route('uploads.media'), fd, {
        onUploadProgress: (e) => {
            if (e.total) uploadProgress.value = Math.round((e.loaded / e.total) * 100);
        },
    }).then((res) => {
        uploaded.value = res.data;
        toast.add({ severity: 'success', summary: t('components.media_picker.upload_ok'), life: 3000 });
    }).catch(() => {
        toast.add({ severity: 'error', summary: t('components.media_picker.upload_error'), life: 5000 });
    }).finally(() => {
        uploading.value = false;
    });
};

const YOUTUBE_RE = /(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([\w-]{6,})/i;
const VIMEO_RE = /vimeo\.com\/(?:video\/)?(\d+)/i;

const detectKind = (url) => {
    if (YOUTUBE_RE.test(url) || VIMEO_RE.test(url)) return 'video';
    if (/\.(jpe?g|png|gif|webp|svg|avif|bmp)(\?|#|$)/i.test(url)) return 'image';
    if (/\.(mp4|webm|ogg|mov|m4v)(\?|#|$)/i.test(url)) return 'video';
    return null;
};

const toEmbedUrl = (url) => {
    const yt = url.match(YOUTUBE_RE);
    if (yt) return `https://www.youtube.com/embed/${yt[1]}`;
    const vm = url.match(VIMEO_RE);
    if (vm) return `https://player.vimeo.com/video/${vm[1]}`;
    return url;
};

const linkKind = computed(() => detectKind(linkUrl.value.trim()));

const isSafeUrl = (url) => {
    try {
        const u = new URL(url);
        return u.protocol === 'http:' || u.protocol === 'https:';
    } catch {
        return false;
    }
};

const linkEmbedUrl = computed(() => (isSafeUrl(linkUrl.value.trim()) ? toEmbedUrl(linkUrl.value.trim()) : ''));

const canInsertLink = computed(() => {
    const url = linkUrl.value.trim();
    if (!isSafeUrl(url)) return false;
    if (props.accept === 'image') return linkKind.value === 'image';
    if (props.accept === 'video') return linkKind.value === 'video';
    return linkKind.value !== null;
});

const insertLink = () => {
    if (!canInsertLink.value) {
        linkError.value = true;
        return;
    }
    emit('insert', {
        type: linkKind.value,
        url: linkEmbedUrl.value,
        name: linkUrl.value.trim(),
    });
    emit('update:visible', false);
};

const insertUploaded = () => {
    if (!uploaded.value) return;
    emit('insert', {
        type: uploaded.value.type,
        url: uploaded.value.url,
        name: uploaded.value.name,
    });
    emit('update:visible', false);
};

const canInsertUploaded = computed(() => {
    if (!uploaded.value) return false;
    if (props.accept === 'image') return uploaded.value.type === 'image';
    if (props.accept === 'video') return uploaded.value.type === 'video';
    return true;
});
</script>

<template>
    <SncDialog
        :visible="visible"
        :title="t('components.media_picker.title')"
        :subtitle="t('components.media_picker.subtitle')"
        icon="pi-images"
        width="560px"
        @update:visible="emit('update:visible', $event)"
    >
        <div class="space-y-4">
            <SelectButton
                v-model="mode"
                :options="modeOptions"
                optionLabel="label"
                optionValue="value"
                :allowEmpty="false"
                class="w-full"
                pt:options="w-full"
            />

            <!-- MODE APPAREIL -->
            <div v-if="mode === 'device'" class="space-y-3">
                <input
                    ref="fileInput"
                    type="file"
                    :accept="fileAccept"
                    class="hidden"
                    @change="onFileChange"
                />
                <div
                    v-if="!pickedFile"
                    class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 p-8 text-center"
                >
                    <i class="pi pi-cloud-upload text-3xl text-slate-400" aria-hidden="true"></i>
                    <p class="mt-3 text-sm text-slate-600 font-medium">{{ t('components.media_picker.device_hint') }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ t('components.media_picker.device_formats') }}</p>
                    <Button
                        :label="t('components.media_picker.choose_file')"
                        icon="pi pi-mobile"
                        class="mt-4"
                        @click="openFilePicker"
                    />
                </div>
                <div v-else class="space-y-3">
                    <div class="flex items-center gap-4 p-3 bg-white border border-slate-200 rounded-xl">
                        <img
                            v-if="isImageFile(pickedFile)"
                            :src="pickedFileUrl"
                            class="w-16 h-16 object-cover rounded-lg flex-shrink-0"
                            alt=""
                        />
                        <div
                            v-else
                            class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0"
                        >
                            <i class="pi pi-video text-2xl text-slate-400" aria-hidden="true"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ pickedFile.name }}</p>
                            <p class="text-xs text-slate-500">{{ formatFileSize(pickedFile.size) }}</p>
                        </div>
                        <Button
                            icon="pi pi-times"
                            severity="secondary"
                            text
                            rounded
                            :disabled="uploading"
                            :aria-label="t('components.media_picker.remove_file')"
                            @click="pickedFile = null; uploaded = null"
                        />
                    </div>
                    <ProgressBar
                        v-if="uploading"
                        :value="uploadProgress"
                        :showValue="false"
                        :aria-label="t('components.media_picker.uploading')"
                    />
                    <Button
                        v-if="!uploaded && !uploading"
                        :label="t('components.media_picker.upload_now')"
                        icon="pi pi-upload"
                        @click="uploadPicked"
                    />
                    <div
                        v-if="uploaded"
                        class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2"
                    >
                        <i class="pi pi-check-circle" aria-hidden="true"></i>
                        <span>{{ t('components.media_picker.upload_ok') }}</span>
                    </div>
                </div>
            </div>

            <!-- MODE LIEN -->
            <div v-else class="space-y-3">
                <div class="space-y-1.5">
                    <label for="media-picker-link" class="text-sm font-medium text-slate-700">
                        {{ t('components.media_picker.link_label') }}
                    </label>
                    <InputText
                        id="media-picker-link"
                        v-model="linkUrl"
                        fluid
                        :placeholder="t('components.media_picker.link_placeholder')"
                        :class="{ 'p-invalid': linkError }"
                    />
                    <p v-if="linkError" class="text-xs text-red-600">{{ t('components.media_picker.link_error') }}</p>
                    <p v-else class="text-xs text-slate-500">{{ t('components.media_picker.link_help') }}</p>
                </div>
                <div
                    v-if="canInsertLink && linkEmbedUrl"
                    class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100"
                >
                    <img
                        v-if="linkKind === 'image'"
                        :src="linkUrl.trim()"
                        class="max-h-48 w-full object-contain bg-white"
                        alt=""
                    />
                    <iframe
                        v-else
                        :src="linkEmbedUrl"
                        class="w-full h-48"
                        allowfullscreen
                        :title="t('components.media_picker.video_preview')"
                    ></iframe>
                </div>
            </div>
        </div>

        <template #footer>
            <Button
                :label="t('components.media_picker.cancel')"
                severity="secondary"
                text
                @click="emit('update:visible', false)"
            />
            <Button
                v-if="mode === 'device'"
                :label="t('components.media_picker.insert')"
                icon="pi pi-check"
                :disabled="!canInsertUploaded"
                @click="insertUploaded"
            />
            <Button
                v-else
                :label="t('components.media_picker.insert')"
                icon="pi pi-check"
                :disabled="!canInsertLink"
                @click="insertLink"
            />
        </template>
    </SncDialog>
</template>
