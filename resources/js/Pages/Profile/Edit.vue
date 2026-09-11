<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
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
    <Head title="Mon Profil" />

    <AppLayout>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <div class="md:col-span-12">
                <div class="card bg-surface-0 shadow-sm border-round p-6">
                    <h2 class="text-2xl font-bold mb-4 text-surface-900 border-b pb-4">Paramètres du profil</h2>
                    
                    <div class="flex flex-col gap-8">
                        <div class="p-6 bg-surface-50 border border-surface-200 rounded-xl">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-2xl"
                            />
                        </div>

                        <div class="p-6 bg-surface-50 border border-surface-200 rounded-xl">
                            <UpdatePasswordForm class="max-w-2xl" />
                        </div>

                        <div class="p-6 bg-red-50 border border-red-200 rounded-xl">
                            <DeleteUserForm class="max-w-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
