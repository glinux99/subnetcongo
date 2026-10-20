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
    <AppLayout>
        <Head title="Profil Utilisateur" />

        <div class="min-h-screen bg-[#F8FAFC] pb-20">
            <!-- HEADER SECTION -->
            <div class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm backdrop-blur-md bg-white/80">
                <div class="max-w-[1800px] mx-auto px-6 py-4 flex justify-between items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow-xl shadow-blue-100">
                            <i class="pi pi-user text-white text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Paramètres</span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight italic">Profil Utilisateur</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1200px] mx-auto px-6 mt-10 space-y-8">
                <!-- PROFILE INFO FORM -->
                <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <!-- UPDATE PASSWORD FORM -->
                <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- DELETE USER FORM -->
                <div class="bg-white rounded-[1rem] p-8 border border-red-100 shadow-sm">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
