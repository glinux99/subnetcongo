<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Mot de passe oublié" />
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center w-full sm:w-auto">
            <div class="w-full sm:w-[36rem] bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                <div class="text-center mb-8">
                    <img src="/assets/media/logos/logo.png" alt="SubnetCongo Logo" class="h-16 inline-block mb-4">
                    <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Mot de passe oublié ?</div>
                    <span class="text-muted-color font-medium text-sm block mb-4">
                        Pas de problème. Indiquez-nous votre adresse e-mail et nous vous enverrons un lien de réinitialisation.
                    </span>
                </div>

                <div v-if="status" class="mb-6 font-medium text-sm text-green-600 bg-green-50 p-4 rounded-md">
                    {{ status }}
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label for="email" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">Adresse e-mail</label>
                        <InputText id="email" type="email" placeholder="Entrez votre adresse e-mail" class="w-full" v-model="form.email" required autofocus />
                        <Message v-if="form.errors.email" class="mt-2" severity="error" :closable="false">{{ form.errors.email }}</Message>
                    </div>

                    <div class="flex items-center justify-between mt-8">
                        <Link :href="route('login')" class="text-sm font-medium text-primary hover:underline">
                            Retour à la connexion
                        </Link>
                        <Button type="submit" label="Envoyer le lien" class="w-full sm:w-auto" :loading="form.processing"></Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
