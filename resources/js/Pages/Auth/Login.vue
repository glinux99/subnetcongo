<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const socialLoginProcessing = ref(null);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

const socialLogin = (provider) => {
    socialLoginProcessing.value = provider;
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
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">{{ t('pages.auth_login.welcome_title') }}</div>
                        <span class="text-muted-color font-medium text-sm">{{ t('pages.auth_login.subtitle') }}</span>
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="email1" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">{{ t('pages.auth_login.email_label') }}</label>
                            <InputText id="email1" type="text" :placeholder="t('pages.auth_login.email_placeholder')" class="w-full w-[32rem] mb-4" v-model="form.email" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error">{{ form.errors.email }}</Message>

                            <label for="password1" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">{{ t('pages.auth_login.password_label') }}</label>
                            <Password id="password1" v-model="form.password" :placeholder="t('pages.auth_login.password_placeholder')" :toggleMask="true" class="w-full w-[32rem] mb-4" fluid :feedback="false"></Password>
                            <Message v-if="form.errors.password" class="mb-4" severity="error">{{ form.errors.password }}</Message>

                            <div class="flex items-center justify-between mt-2 mb-8 gap-8">
                                <div class="flex items-center">
                                    <Checkbox v-model="form.remember" id="rememberme1" binary class="mr-2"></Checkbox>
                                    <label for="rememberme1">{{ t('pages.auth_login.remember_me') }}</label>
                                </div>
                                <a class="font-medium no-underline ml-2 text-right cursor-pointer text-primary">{{ t('pages.auth_login.forgot_password') }}</a>

                            </div>
                               <Button type="submit" :label="t('pages.auth_login.submit')" class="w-full" :loading="form.processing"></Button>
                               <div class="my-4 flex items-center">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="mx-4 flex-shrink text-sm text-gray-500">{{ t('pages.auth_login.or') }}</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>
                            <div class="flex flex-col gap-4 mb-4">
                                <Button @click="socialLogin('google')" :loading="socialLoginProcessing === 'google'" :label="t('pages.auth_login.login_google')" icon="pi pi-google" class="p-button-outlined p-button-secondary w-full"></Button>
                                <Button @click="socialLogin('facebook')" :loading="socialLoginProcessing === 'facebook'" :label="t('pages.auth_login.login_facebook')" icon="pi pi-facebook" class="p-button-outlined p-button-secondary w-full"></Button>
                                <Button @click="socialLogin('linkedin')" :loading="socialLoginProcessing === 'linkedin'" :label="t('pages.auth_login.login_linkedin')" icon="pi pi-linkedin" class="p-button-outlined p-button-secondary w-full d-none"></Button>
                                 <div class="text-center mt-6 text-sm text-muted-color">
                                {{ t('pages.auth_login.no_account') }}
                                <Link :href="route('register')" class="text-primary font-semibold">{{ t('pages.auth_login.register_link') }}</Link>
                            </div>
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
