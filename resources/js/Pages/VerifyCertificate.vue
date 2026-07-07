<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Message from 'primevue/message';

const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['valid', 'invalid'].includes(value),
    },
    message: {
        type: String,
        default: '',
    },
    certificate: {
        type: Object,
        default: () => ({}),
    },
});

const pageTitle = computed(() => {
    if (props.status === 'valid') {
        return `Vérification : ${props.certificate.student}`;
    }
    return 'Document Invalide';
});

const verificationStatus = computed(() => {
    if (!props.certificate || Object.keys(props.certificate).length === 0) {
        return {
            label: 'Invalide',
            severity: 'danger',
            color: 'text-red-700',
            borderColor: 'border-red-500',
            icon: 'pi pi-exclamation-triangle',
            message: props.message || 'Informations manquantes.'
        };
    }
    if (props.certificate.is_revoked) {
        return {
            label: 'Révoqué',
            severity: 'danger',
            color: 'text-red-700',
            borderColor: 'border-red-600',
            icon: 'pi pi-ban',
            message: `Ce titre a été révoqué le ${props.certificate.revoked_at || ''}. Raison : ${props.certificate.revocation_reason || 'Non spécifiée'}.`
        };
    }
    if (props.certificate.is_expired) {
        return {
            label: 'Expiré',
            severity: 'warning',
            color: 'text-amber-700',
            borderColor: 'border-amber-500',
            icon: 'pi pi-history',
            message: 'Ce certificat a atteint sa date de fin de validité.'
        };
    }
    return {
        label: 'Authentique & Valide',
        severity: 'success',
        color: 'text-emerald-700',
        borderColor: 'border-emerald-500',
        icon: 'pi pi-check-circle',
        message: 'Ce document numérique est authentique et a été émis par notre centre de formation.'
    };
});
</script>

<template>
    <Head :title="pageTitle" />

    <!-- Arrière-plan stylisé -->
    <div class="min-h-screen bg-[#f8fafc] flex items-center justify-center p-6 font-sans relative overflow-hidden">
        <div class="absolute inset-0 z-0 opacity-40">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-100 blur-3xl"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-emerald-50 blur-3xl"></div>
        </div>

        <div class="w-full max-w-3xl z-10">

            <!-- Logo / Header de la plateforme -->
            <div class="flex justify-center mb-8">
                <div class="flex items-center gap-2">
                    <div class="bg-slate-900 text-white p-2 rounded-lg font-bold text-xl">SC</div>
                    <span class="text-xl font-black text-slate-800 tracking-tighter uppercase">SubnetCongo <span class="text-blue-600 font-light text-sm">LMS</span></span>
                </div>
            </div>

            <!-- État : INVALID -->
            <Card v-if="status === 'invalid'" class="shadow-2xl border-t-4 border-red-500 rounded-xl overflow-hidden">
                <template #content>
                    <div class="py-6 text-center">
                        <div class="bg-red-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="pi pi-times text-4xl text-red-500"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 mb-2">Vérification Échouée</h2>
                        <p class="text-slate-600 max-w-md mx-auto leading-relaxed">
                            {{ message || "Le code de vérification fourni ne correspond à aucun enregistrement dans notre base de données sécurisée." }}
                        </p>
                        <Divider class="my-8" />
                        <button onclick="window.history.back()" class="text-slate-500 hover:text-slate-800 transition-colors flex items-center justify-center gap-2 mx-auto font-medium">
                            <i class="pi pi-arrow-left"></i> Retour
                        </button>
                    </div>
                </template>
            </Card>

            <!-- État : VALID -->
            <Card v-else class="shadow-2xl border-0 rounded-2xl overflow-hidden bg-white">
                <template #header>
                    <!-- Barre d'état supérieure -->
                    <div :class="['h-2 w-full', verificationStatus.severity === 'success' ? 'bg-emerald-500' : 'bg-amber-500']"></div>
                </template>

                <template #content>
                    <div class="p-2 md:p-6">
                        <!-- En-tête du certificat -->
                        <div class="flex flex-col md:flex-row items-start justify-between gap-6 mb-10 border-b border-slate-100 pb-8">
                            <div>
                                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">
                                    Preuve de Certification
                                </h1>
                                <div class="flex items-center gap-2 text-slate-500">
                                    <i class="pi pi-shield text-blue-600"></i>
                                    <span class="text-sm font-medium">Document scellé numériquement</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <Tag :severity="verificationStatus.severity" class="px-4 py-2 text-sm uppercase tracking-widest font-bold shadow-sm">
                                    <template #icon>
                                        <i :class="[verificationStatus.icon, 'mr-2']"></i>
                                    </template>
                                    {{ verificationStatus.label }}
                                </Tag>
                            </div>
                        </div>

                        <!-- Alerte de statut -->
                        <div :class="['p-4 rounded-xl mb-10 border flex items-start gap-4 transition-all',
                            verificationStatus.severity === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-amber-50 border-amber-100 text-amber-800']">
                            <i :class="[verificationStatus.icon, 'text-xl mt-1']"></i>
                            <p class="text-sm md:text-base leading-relaxed font-medium">
                                {{ verificationStatus.message }}
                            </p>
                        </div>

                        <!-- Grille d'informations -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Titulaire du Certificat</p>
                                <p class="text-2xl font-bold text-slate-900 leading-tight">{{ certificate.student }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Date d'Émission</p>
                                <p class="text-lg font-semibold text-slate-700 flex items-center gap-2">
                                    <i class="pi pi-calendar text-slate-400"></i>
                                    {{ certificate.issued_at }}
                                </p>
                            </div>

                            <div class="md:col-span-2 space-y-1 bg-slate-50 p-4 rounded-lg">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Programme / Formation</p>
                                <p class="text-xl font-bold text-blue-900 leading-snug">{{ certificate.course }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Évaluation</p>
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-slate-800">{{ certificate.score ? `${certificate.score}%` : 'Réussite' }}</span>
                                    <div class="h-2 w-24 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500" :style="{width: (certificate.score || 100) + '%'}"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">ID de Vérification</p>
                                <p class="font-mono text-sm bg-slate-100 px-2 py-1 rounded text-slate-600 break-all border border-slate-200">
                                    {{ certificate.id }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <template #footer>
                    <div class="px-8 py-6 bg-slate-50/80 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-400 mb-2 italic">
                            Ce document a été généré électroniquement. Aucune signature manuscrite n'est requise.
                        </p>
                        <p class="text-sm font-semibold text-slate-600">
                            Trust Center <span class="text-blue-600">SubnetCongo</span> — Unité de Certification Académique
                        </p>
                    </div>
                </template>
            </Card>

            <!-- Liens d'aide -->
            <div class="mt-8 text-center">
                <p class="text-slate-400 text-sm italic">
                    Une question sur cette certification ? <a href="#" class="text-blue-500 underline underline-offset-4 hover:text-blue-700">Contacter le support</a>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Ajout d'une police plus élégante si nécessaire */
:deep(.p-tag) {
    border-radius: 6px;
}
</style>
