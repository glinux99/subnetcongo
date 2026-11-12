<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
    <Head :title="t('pages.auth_verify_email.title')" />
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center w-full sm:w-auto">
            <div class="w-full sm:w-[36rem] bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                <div class="text-center mb-8">
                    <img src="/assets/media/logos/logo.png" alt="SubnetCongo Logo" class="h-16 inline-block mb-4">
                    <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">{{ t('pages.auth_verify_email.heading') }}</div>
                    <span class="text-muted-color font-medium text-sm block mb-4">
                        {{ t('pages.auth_verify_email.notice') }}
                    </span>
                </div>

                <div v-if="verificationLinkSent" class="mb-6 font-medium text-sm text-green-600 bg-green-50 p-4 rounded-md">
                    {{ t('pages.auth_verify_email.link_sent') }}
                </div>

                <form @submit.prevent="submit">
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <Button type="submit" :label="t('pages.auth_verify_email.resend')" class="w-full sm:w-auto" :loading="form.processing"></Button>

                        <Link :href="route('logout')" method="post" as="button" class="text-sm font-medium text-surface-500 hover:text-surface-900 hover:underline transition duration-150 ease-in-out w-full sm:w-auto text-center sm:text-right">
                            {{ t('pages.auth_verify_email.logout') }}
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
