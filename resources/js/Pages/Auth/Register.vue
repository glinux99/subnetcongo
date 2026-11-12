<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const socialRegisterProcessing = ref(null);

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};

const socialRegister = (provider) => {
    socialRegisterProcessing.value = provider;
    window.location.href = route('socialite.redirect', provider);
};
</script>

<template>
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div>
                <div class="w-full bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                    <div class="text-center mb-8">
                        <img src="/assets/media/logos/logo.png" alt="" class="h-16 inline-block mb-4">
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">{{ t('pages.auth_register.title') }}</div>
                        <span class="text-muted-color font-medium text-sm">{{ t('pages.auth_register.subtitle') }}</span>
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="name" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">{{ t('pages.auth_register.name_label') }}</label>
                            <InputText id="name" type="text" :placeholder="t('pages.auth_register.name_placeholder')" class="w-full w-[32rem] mb-4" v-model="form.name" />
                            <Message v-if="form.errors.name" class="mb-4" severity="error">{{ form.errors.name }}</Message>

                            <label for="email" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">{{ t('pages.auth_register.email_label') }}</label>
                            <InputText id="email" type="email" :placeholder="t('pages.auth_register.email_placeholder')" class="w-full w-[32rem] mb-4" v-model="form.email" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error">{{ form.errors.email }}</Message>

                            <label for="password" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">{{ t('pages.auth_register.password_label') }}</label>
                            <Password id="password" v-model="form.password" :placeholder="t('pages.auth_register.password_placeholder')" :toggleMask="true" class="w-full w-[32rem] mb-4" fluid></Password>
                            <Message v-if="form.errors.password" class="mb-4" severity="error">{{ form.errors.password }}</Message>

                            <label for="password_confirmation" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">{{ t('pages.auth_register.password_confirm_label') }}</label>
                            <Password id="password_confirmation" v-model="form.password_confirmation" :placeholder="t('pages.auth_register.password_confirm_placeholder')" :toggleMask="true" :feedback="false" class="w-full w-[32rem] mb-4" fluid></Password>
                            <Message v-if="form.errors.password_confirmation" class="mb-4" severity="error">{{ form.errors.password_confirmation }}</Message>

                            <div class="flex items-start mt-2 mb-6 gap-3">
                                <Checkbox v-model="form.terms" id="terms" binary class="mt-1"></Checkbox>
                                <label for="terms" class="text-sm">
                                    {{ t('pages.auth_register.terms_accept') }} <a href="#" class="text-primary font-medium">{{ t('pages.auth_register.terms_conditions') }}</a> {{ t('pages.auth_register.terms_and') }} <a href="#" class="text-primary font-medium">{{ t('pages.auth_register.terms_privacy') }}</a>.
                                </label>
                            </div>
                            <Message v-if="form.errors.terms" class="mb-4" severity="error">{{ form.errors.terms }}</Message>

                            <Button type="submit" :label="t('pages.auth_register.submit')" class="w-full" :loading="form.processing" :disabled="!form.terms"></Button>

                            <div class="my-4 flex items-center">
                                <div class="flex-grow border-t border-gray-300"></div>
                                <span class="mx-4 flex-shrink text-sm text-gray-500">{{ t('pages.auth_register.or') }}</span>
                                <div class="flex-grow border-t border-gray-300"></div>
                            </div>

                            <div class="flex flex-col gap-4 mb-4">
                                <Button @click="socialRegister('google')" :loading="socialRegisterProcessing === 'google'" :label="t('pages.auth_register.register_google')" icon="pi pi-google" class="p-button-outlined p-button-secondary w-full"></Button>
                                <Button @click="socialRegister('facebook')" :loading="socialRegisterProcessing === 'facebook'" :label="t('pages.auth_register.register_facebook')" icon="pi pi-facebook" class="p-button-outlined p-button-secondary w-full"></Button>
                            </div>

                            <div class="text-center mt-6 text-sm text-muted-color">
                                {{ t('pages.auth_register.has_account') }}
                                <Link :href="route('login')" class="text-primary font-semibold">{{ t('pages.auth_register.login_link') }}</Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
