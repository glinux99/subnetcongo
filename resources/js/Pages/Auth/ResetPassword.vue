<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Réinitialiser le mot de passe" />
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center w-full sm:w-auto">
            <div class="w-full sm:w-[36rem] bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                <div class="text-center mb-8">
                    <img src="/assets/media/logos/logo.png" alt="SubnetCongo Logo" class="h-16 inline-block mb-4">
                    <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Nouveau mot de passe</div>
                    <span class="text-muted-color font-medium text-sm block mb-4">
                        Créez un nouveau mot de passe pour votre compte.
                    </span>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label for="email" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">Adresse e-mail</label>
                        <InputText id="email" type="email" placeholder="Adresse e-mail" class="w-full" v-model="form.email" required autofocus readonly />
                        <Message v-if="form.errors.email" class="mt-2" severity="error" :closable="false">{{ form.errors.email }}</Message>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Nouveau mot de passe</label>
                        <Password id="password" v-model="form.password" placeholder="Nouveau mot de passe" :toggleMask="true" class="w-full" fluid></Password>
                        <Message v-if="form.errors.password" class="mt-2" severity="error" :closable="false">{{ form.errors.password }}</Message>
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Confirmer le mot de passe</label>
                        <Password id="password_confirmation" v-model="form.password_confirmation" placeholder="Retapez le mot de passe" :toggleMask="true" :feedback="false" class="w-full" fluid></Password>
                        <Message v-if="form.errors.password_confirmation" class="mt-2" severity="error" :closable="false">{{ form.errors.password_confirmation }}</Message>
                    </div>

                    <div class="flex items-center justify-end mt-8">
                        <Button type="submit" label="Réinitialiser" class="w-full" :loading="form.processing"></Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
