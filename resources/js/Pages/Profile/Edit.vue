<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageCard from '@/Components/PageCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.profile.head_title')" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ ULTRA PRO V50 -->
            <PageHeader
                :title="t('pages.profile.title')"
                :subtitle="t('pages.profile.subtitle')"
                icon="pi-user"
                :badge="t('pages.profile.badge')"
                accent="blue"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-8">
                <!-- FORMULAIRE INFORMATIONS PROFIL -->
                <PageCard :title="t('pages.profile.personal_info_title')" :subtitle="t('pages.profile.personal_info_subtitle')">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl"
                    />
                </PageCard>

                <!-- FORMULAIRE MOT DE PASSE -->
                <PageCard :title="t('pages.profile.security_title')" :subtitle="t('pages.profile.security_subtitle')">
                    <UpdatePasswordForm class="max-w-2xl" />
                </PageCard>

                <!-- FORMULAIRE ZONE DE DANGER -->
                <PageCard :title="t('pages.profile.danger_title')" :subtitle="t('pages.profile.danger_subtitle')">
                    <DeleteUserForm class="max-w-2xl" />
                </PageCard>
            </div>
        </div>
    </AppLayout>
</template>
