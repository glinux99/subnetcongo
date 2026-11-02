<script setup>
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
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
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Mettre à jour le Mot de Passe</h2>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                Assurez-vous que votre compte utilise un mot de passe robuste et sécurisé.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label for="current_password" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">Mot de passe actuel</label>
                <InputText
                    id="current_password"
                    v-model="form.current_password"
                    type="password"
                    class="w-full !rounded-xl"
                    autocomplete="current-password"
                />
                <Message v-if="form.errors.current_password" severity="error" size="small" variant="simple" class="mt-1">
                    {{ form.errors.current_password }}
                </Message>
            </div>

            <div>
                <label for="password" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">Nouveau mot de passe</label>
                <InputText
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full !rounded-xl"
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple" class="mt-1">
                    {{ form.errors.password }}
                </Message>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-black uppercase tracking-widest text-slate-600 mb-2">Confirmer le mot de passe</label>
                <InputText
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full !rounded-xl"
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password_confirmation" severity="error" size="small" variant="simple" class="mt-1">
                    {{ form.errors.password_confirmation }}
                </Message>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <Button
                    type="submit"
                    label="Mettre à jour"
                    icon="pi pi-lock"
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
                        <i class="pi pi-check-circle"></i> Mot de passe mis à jour.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
