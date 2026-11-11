<script setup>
// ============================================================================
// ImageUploader — champ image réutilisable pour toutes les pages admin
//   • Upload direct depuis PC / téléphone (fichier vers /admin/uploads/image)
//   • Fallback : coller un lien (URL externe)
//   • Aperçu avant enregistrement
// ============================================================================
import { computed, ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: "Image" },
    hint: { type: String, default: 'Image (jpg, png, webp… max 6 Mo)' },
    accept: { type: String, default: 'image/*' },
});

const emit = defineEmits(['update:modelValue']);
const uploading = ref(false);
const error = ref('');
const fileInput = ref(null);

const imgUrl = computed(() => props.modelValue);

const pickFile = () => fileInput.value?.click();

const onFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    error.value = '';
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('image', file);
        const { data } = await window.axios.post(route('admin.uploads.image'), fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        emit('update:modelValue', data.path || data.url);
    } catch (err) {
        error.value = err?.response?.data?.message
            || err?.response?.data?.errors?.image?.[0]
            || "Échec de l'envoi de l'image.";
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
};

const onUrl = (value) => {
    error.value = '';
    emit('update:modelValue', value);
};

const clear = () => emit('update:modelValue', '');
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-semibold text-slate-700">{{ label }}</label>
        <div v-if="imgUrl" class="relative group inline-block">
            <img :src="imgUrl" alt="" class="h-36 w-56 object-cover rounded-xl border border-slate-200 shadow-sm bg-slate-50" @error="error = 'Image introuvable ou URL invalide'" />
            <button type="button" @click="clear"
                class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-rose-600 text-white text-sm shadow hover:bg-rose-700">
                <i class="pi pi-times"></i>
            </button>
        </div>
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <input ref="fileInput" type="file" :accept="accept" class="hidden" @change="onFile" />
                <Button type="button" icon="pi pi-upload" label="Depuis mon appareil"
                    :loading="uploading" @click="pickFile" class="!rounded-xl" />
                <span class="text-xs text-slate-400">ou</span>
                <InputText
                    :model-value="modelValue"
                    placeholder="https://…"
                    class="flex-1 !rounded-xl"
                    @update:model-value="onUrl"
                />
            </div>
            <p v-if="uploading" class="text-xs text-blue-600"><i class="pi pi-spin pi-spinner"></i> Envoi en cours…</p>
            <p v-if="error" class="text-xs text-rose-600">{{ error }}</p>
            <p v-else class="text-[10px] text-slate-400">{{ hint }}</p>
        </div>
    </div>
</template>
