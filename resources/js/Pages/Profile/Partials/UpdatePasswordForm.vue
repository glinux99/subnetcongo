<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                // PrimeVue Password component focus might need $el
                if(passwordInput.value && passwordInput.value.$el) {
                    const input = passwordInput.value.$el.querySelector('input');
                    if(input) input.focus();
                }
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                if(currentPasswordInput.value && currentPasswordInput.value.$el) {
                    const input = currentPasswordInput.value.$el.querySelector('input');
                    if(input) input.focus();
                }
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-bold text-surface-900 mb-2">Mettre à jour le mot de passe</h2>
            <p class="text-sm text-surface-500 mb-6">
                Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sécurisé.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="flex flex-col gap-4">
            <div>
                <label for="current_password" class="block text-surface-900 font-medium mb-2">Mot de passe actuel</label>
                <Password
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    class="w-full"
                    fluid
                    :feedback="false"
                    toggleMask
                    autocomplete="current-password"
                />
                <Message v-if="form.errors.current_password" severity="error" class="mt-2" :closable="false">{{ form.errors.current_password }}</Message>
            </div>

            <div>
                <label for="password" class="block text-surface-900 font-medium mb-2">Nouveau mot de passe</label>
                <Password
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    class="w-full"
                    fluid
                    toggleMask
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password" severity="error" class="mt-2" :closable="false">{{ form.errors.password }}</Message>
            </div>

            <div>
                <label for="password_confirmation" class="block text-surface-900 font-medium mb-2">Confirmer le mot de passe</label>
                <Password
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="w-full"
                    fluid
                    :feedback="false"
                    toggleMask
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password_confirmation" severity="error" class="mt-2" :closable="false">{{ form.errors.password_confirmation }}</Message>
            </div>

            <div class="flex items-center gap-4 mt-4">
                <Button type="submit" label="Mettre à jour" icon="pi pi-key" :loading="form.processing"></Button>

                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                >
                    <span v-if="form.recentlySuccessful" class="text-sm font-medium text-green-600 flex items-center gap-2">
                        <i class="pi pi-check-circle"></i> Mot de passe mis à jour.
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
