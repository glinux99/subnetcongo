<script setup>
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-xl font-black text-red-600 tracking-tight">{{ t('pages.profile.delete_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                {{ t('pages.profile.delete_notice') }}
            </p>
        </header>

        <Button
            :label="t('pages.profile.delete_button')"
            icon="pi pi-trash"
            severity="danger"
            class="!rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-5 !py-3 shadow-lg shadow-red-500/20"
            @click="confirmUserDeletion"
        />

        <Dialog
            v-model:visible="confirmingUserDeletion"
            modal
            :header="t('pages.profile.delete_confirm_header')"
            :style="{ width: '500px' }"
            class="!rounded-2xl"
        >
            <div class="space-y-4 pt-2">
                <p class="text-sm text-slate-600 leading-relaxed font-medium">
                    {{ t('pages.profile.delete_confirm_message') }}
                </p>

                <div>
                    <label for="password_delete" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">{{ t('pages.profile.password_label') }}</label>
                    <InputText
                        id="password_delete"
                        v-model="form.password"
                        type="password"
                        class="w-full !rounded-xl"
                        :placeholder="t('pages.profile.password_placeholder')"
                        @keyup.enter="deleteUser"
                    />
                    <Message v-if="form.errors.password" severity="error" size="small" variant="simple" class="mt-1">
                        {{ form.errors.password }}
                    </Message>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3 pt-3">
                    <Button
                        :label="t('pages.profile.cancel')"
                        icon="pi pi-times"
                        outlined
                        class="!rounded-xl !font-bold !text-xs"
                        @click="closeModal"
                    />
                    <Button
                        :label="t('pages.profile.delete_confirm_button')"
                        icon="pi pi-trash"
                        severity="danger"
                        :loading="form.processing"
                        class="!rounded-xl !font-black !uppercase !tracking-widest !text-xs shadow-lg shadow-red-500/20"
                        @click="deleteUser"
                    />
                </div>
            </template>
        </Dialog>
    </section>
</template>
