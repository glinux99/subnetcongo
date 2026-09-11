<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
        onError: () => {
            if(passwordInput.value && passwordInput.value.$el) {
                const input = passwordInput.value.$el.querySelector('input');
                if(input) input.focus();
            }
        },
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
            <h2 class="text-xl font-bold text-red-600 mb-2">Supprimer le compte</h2>
            <p class="text-sm text-red-900 mb-6">
                Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées. Avant de supprimer votre compte, veuillez télécharger toutes les données ou informations que vous souhaitez conserver.
            </p>
        </header>

        <Button label="Supprimer le compte" icon="pi pi-trash" severity="danger" @click="confirmUserDeletion"></Button>

        <Dialog v-model:visible="confirmingUserDeletion" modal header="Êtes-vous sûr de vouloir supprimer votre compte ?" :style="{ width: '40rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
            <p class="m-0 mb-4 text-surface-600">
                Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées. Veuillez saisir votre mot de passe pour confirmer que vous souhaitez supprimer définitivement votre compte.
            </p>

            <div class="flex flex-col gap-2 mb-4">
                <Password
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    class="w-full"
                    fluid
                    :feedback="false"
                    toggleMask
                    placeholder="Votre mot de passe"
                    @keyup.enter="deleteUser"
                />
                <Message v-if="form.errors.password" severity="error" :closable="false">{{ form.errors.password }}</Message>
            </div>

            <template #footer>
                <Button label="Annuler" icon="pi pi-times" text @click="closeModal" />
                <Button label="Supprimer le compte" icon="pi pi-trash" severity="danger" @click="deleteUser" :loading="form.processing" autofocus />
            </template>
        </Dialog>
    </section>
</template>
