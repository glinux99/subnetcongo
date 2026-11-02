<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageCard from '@/Components/PageCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

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
        <Head title="Profil Utilisateur" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ ULTRA PRO V50 -->
            <PageHeader
                title="Mon Profil Utilisateur"
                subtitle="Gérez les informations de votre compte, mot de passe et sécurité."
                icon="pi-user"
                badge="Paramètres"
                accent="blue"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-8">
                <!-- FORMULAIRE INFORMATIONS PROFIL -->
                <PageCard title="Informations Personnelles" subtitle="Vos identifiants et coordonnées de contact">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl"
                    />
                </PageCard>

                <!-- FORMULAIRE MOT DE PASSE -->
                <PageCard title="Sécurité et Mot de Passe" subtitle="Mettez à jour vos identifiants d'accès">
                    <UpdatePasswordForm class="max-w-2xl" />
                </PageCard>

                <!-- FORMULAIRE ZONE DE DANGER -->
                <PageCard title="Zone Critique" subtitle="Suppression définitive du compte et de vos données">
                    <DeleteUserForm class="max-w-2xl" />
                </PageCard>
            </div>
        </div>
    </AppLayout>
</template>
