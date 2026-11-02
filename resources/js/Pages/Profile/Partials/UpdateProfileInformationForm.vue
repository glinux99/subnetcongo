<script setup>
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
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
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Informations du Profil</h2>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                Mettez à jour les informations de profil et l'adresse e-mail de votre compte.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <label for="name" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">Nom complet</label>
                <InputText
                    id="name"
                    type="text"
                    class="w-full !rounded-xl"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <Message v-if="form.errors.name" severity="error" size="small" variant="simple" class="mt-1">
                    {{ form.errors.name }}
                </Message>
            </div>

            <div>
                <label for="email" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">Adresse E-mail</label>
                <InputText
                    id="email"
                    type="email"
                    class="w-full !rounded-xl"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple" class="mt-1">
                    {{ form.errors.email }}
                </Message>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm mt-2 text-slate-700">
                    Votre adresse e-mail n'est pas encore vérifiée.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-sm font-bold text-blue-600 hover:text-blue-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Cliquez ici pour renvoyer le lien de vérification.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-sm text-emerald-600"
                >
                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <Button
                    type="submit"
                    label="Enregistrer"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-5 !py-3 shadow-lg shadow-blue-500/20"
                />

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-bold text-emerald-600 flex items-center gap-1.5">
                        <i class="pi pi-check-circle"></i> Enregistré avec succès.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
