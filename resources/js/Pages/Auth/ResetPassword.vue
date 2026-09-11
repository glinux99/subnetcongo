<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

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
        <div class="flex flex-col items-center justify-center w-full">
            <div>
                <div class="w-full bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px; max-width: 38rem;">
                    <div class="text-center mb-8">
                        <img src="/assets/media/logos/logo.png" alt="" class="h-16 inline-block mb-4">
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Réinitialisation du mot de passe</div>
                        <span class="text-muted-color font-medium text-sm block mb-4">
                            Veuillez choisir un nouveau mot de passe sécurisé.
                        </span>
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="email" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">Adresse e-mail</label>
                            <InputText id="email" type="email" placeholder="exemple@email.com" class="w-full mb-4" v-model="form.email" autofocus autocomplete="username" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error" :closable="false">{{ form.errors.email }}</Message>

                            <label for="password" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Nouveau mot de passe</label>
                            <Password id="password" v-model="form.password" placeholder="Choisissez un mot de passe sécurisé" :toggleMask="true" class="w-full mb-4" fluid autocomplete="new-password"></Password>
                            <Message v-if="form.errors.password" class="mb-4" severity="error" :closable="false">{{ form.errors.password }}</Message>

                            <label for="password_confirmation" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Confirmer le mot de passe</label>
                            <Password id="password_confirmation" v-model="form.password_confirmation" placeholder="Retapez le mot de passe" :toggleMask="true" :feedback="false" class="w-full mb-6" fluid autocomplete="new-password"></Password>
                            <Message v-if="form.errors.password_confirmation" class="mb-4" severity="error" :closable="false">{{ form.errors.password_confirmation }}</Message>

                            <Button type="submit" label="Réinitialiser mon mot de passe" class="w-full" :loading="form.processing"></Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
