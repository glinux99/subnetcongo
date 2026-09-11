<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-bold text-surface-900 mb-2">Informations du Profil</h2>
            <p class="text-sm text-surface-500 mb-6">
                Mettez à jour les informations de profil et l'adresse e-mail de votre compte.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="flex flex-col gap-4">
            <div>
                <label for="name" class="block text-surface-900 font-medium mb-2">Nom</label>
                <InputText
                    id="name"
                    type="text"
                    class="w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <Message v-if="form.errors.name" severity="error" class="mt-2" :closable="false">{{ form.errors.name }}</Message>
            </div>

            <div>
                <label for="email" class="block text-surface-900 font-medium mb-2">Adresse E-mail</label>
                <InputText
                    id="email"
                    type="email"
                    class="w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <Message v-if="form.errors.email" severity="error" class="mt-2" :closable="false">{{ form.errors.email }}</Message>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-surface-800">
                    Votre adresse e-mail n'est pas vérifiée.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-subnetblue hover:underline font-semibold ml-1"
                    >
                        Cliquez ici pour renvoyer l'e-mail de vérification.
                    </Link>
                </p>

                <Message
                    v-if="status === 'verification-link-sent'"
                    severity="success"
                    class="mt-4"
                >
                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </Message>
            </div>

            <div class="flex items-center gap-4 mt-4">
                <Button type="submit" label="Enregistrer" icon="pi pi-save" :loading="form.processing"></Button>

                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                >
                    <span v-if="form.recentlySuccessful" class="text-sm font-medium text-green-600 flex items-center gap-2">
                        <i class="pi pi-check-circle"></i> Enregistré.
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
