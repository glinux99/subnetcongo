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
        <div class="flex flex-col items-center justify-center w-full">
            <div>
                <div class="w-full bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px; max-width: 38rem;">
                    <div class="text-center mb-8">
                        <img src="/assets/media/logos/logo.png" alt="" class="h-16 inline-block mb-4">
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Mot de passe oublié ?</div>
                        <span class="text-muted-color font-medium text-sm block mb-4">
                            Aucun problème. Indiquez-nous votre adresse e-mail et nous vous enverrons un lien de réinitialisation.
                        </span>
                    </div>

                    <Message v-if="status" severity="success" class="mb-4">
                        {{ status }}
                    </Message>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="email" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">Adresse e-mail</label>
                            <InputText id="email" type="email" placeholder="exemple@email.com" class="w-full mb-4" v-model="form.email" autofocus autocomplete="username" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error" :closable="false">{{ form.errors.email }}</Message>

                            <Button type="submit" label="Envoyer le lien de réinitialisation" class="w-full mt-2" :loading="form.processing"></Button>
                            
                            <div class="text-center mt-6 text-sm text-muted-color">
                                <Link :href="route('login')" class="text-primary font-semibold hover:underline">
                                    <i class="pi pi-arrow-left text-xs mr-1"></i> Retour à la connexion
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
