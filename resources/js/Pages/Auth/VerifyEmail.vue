<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Vérification de l'adresse e-mail" />

    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center w-full">
            <div>
                <div class="w-full bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px; max-width: 38rem;">
                    <div class="text-center mb-8">
                        <img src="/assets/media/logos/logo.png" alt="" class="h-16 inline-block mb-4">
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Vérifiez votre e-mail</div>
                        <span class="text-muted-color font-medium text-sm block mb-4">
                            Merci de votre inscription ! Avant de commencer, pourriez-vous vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer ? Si vous n'avez pas reçu l'e-mail, nous vous en enverrons un autre avec plaisir.
                        </span>
                    </div>

                    <Message v-if="verificationLinkSent" severity="success" class="mb-6">
                        Un nouveau lien de vérification a été envoyé à l'adresse e-mail que vous avez fournie lors de votre inscription.
                    </Message>

                    <form @submit.prevent="submit">
                        <div class="flex flex-col gap-4">
                            <Button type="submit" label="Renvoyer l'e-mail de vérification" class="w-full" :loading="form.processing"></Button>
                            
                            <div class="text-center mt-2 text-sm text-muted-color">
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="text-primary font-semibold hover:underline"
                                    >
                                    Se déconnecter
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
