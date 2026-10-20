<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Confirmer le mot de passe" />
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center w-full sm:w-auto">
            <div class="w-full sm:w-[36rem] bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                <div class="text-center mb-8">
                    <img src="/assets/media/logos/logo.png" alt="SubnetCongo Logo" class="h-16 inline-block mb-4">
                    <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Zone Sécurisée</div>
                    <span class="text-muted-color font-medium text-sm block mb-4">
                        Il s'agit d'une zone sécurisée de l'application. Veuillez confirmer votre mot de passe avant de continuer.
                    </span>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label for="password" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Mot de passe</label>
                        <Password id="password" v-model="form.password" placeholder="Votre mot de passe" :toggleMask="true" :feedback="false" class="w-full" fluid autofocus></Password>
                        <Message v-if="form.errors.password" class="mt-2" severity="error" :closable="false">{{ form.errors.password }}</Message>
                    </div>

                    <div class="flex justify-end mt-8">
                        <Button type="submit" label="Confirmer" class="w-full sm:w-auto" :loading="form.processing"></Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
