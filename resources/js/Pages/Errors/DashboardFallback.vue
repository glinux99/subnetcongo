<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - DASHBOARD FALLBACK (CRITICAL ERROR VIEW)
 * ======================================================================================
 * Vue de secours affichée par le DashboardController en cas de défaillance majeure
 * (Timeout DB, Memory Exhaustion, Erreur de requête complexe).
 *
 * UI/UX :
 * - Indépendant du layout global pour garantir l'affichage même si l'app entière souffre.
 * - Design "Enterprise" rassurant avec des appels à l'action clairs.
 * - Zone "Console" pour afficher le message technique renvoyé par le backend.
 */

import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

// --- PRIMEVUE COMPONENTS ---
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    error: {
        type: String,
        default: 'Une anomalie système a empêché l\'agrégation des données analytiques.'
    }
});

const isRetrying = ref(false);
const autoRetryCountdown = ref(30);
let countdownInterval = null;

onMounted(() => {
    // Compte à rebours avant tentative d'auto-rechargement
    countdownInterval = setInterval(() => {
        if (autoRetryCountdown.value > 0) {
            autoRetryCountdown.value--;
        } else {
            clearInterval(countdownInterval);
            retryConnection();
        }
    }, 1000);
});

// --- ACTIONS ---
const retryConnection = () => {
    isRetrying.value = true;
    clearInterval(countdownInterval);

    // On recharge la page actuelle pour relancer le contrôleur
    setTimeout(() => {
        window.location.reload();
    }, 1500);
};

const goBackHome = () => {
    clearInterval(countdownInterval);
    router.visit('/'); // Redirection vers l'accueil (qui utilise un autre contrôleur)
};

const contactSupport = () => {
    window.location.href = "mailto:it-support@subnetcongo.com?subject=Erreur%20Critique%20Dashboard%20Analytics";
};
</script>

<template>
    <Head title="Interruption de Service - SubnetCongo" />

    <div class="min-h-screen bg-slate-900 flex items-center justify-center p-6 relative overflow-hidden font-sans">

        <!-- EFFETS VISUELS D'ARRIÈRE-PLAN (Style Alerte/Serveur) -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 via-orange-500 to-rose-500 animate-gradient-x"></div>
        <div class="absolute -top-[300px] -right-[300px] w-[600px] h-[600px] bg-rose-600/10 rounded-full blur-[120px] pointer-events-none animate-pulse-slow"></div>
        <div class="absolute -bottom-[300px] -left-[300px] w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[120px] pointer-events-none"></div>

        <!-- GRILLE DE FOND (Tech vibe) -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none mix-blend-overlay"></div>

        <!-- CONTENEUR PRINCIPAL -->
        <div class="max-w-2xl w-full relative z-10 animate-fade-in-up">

            <div class="bg-white rounded-[1rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden border border-slate-200">

                <!-- HEADER CARTE -->
                <div class="bg-rose-50 border-b border-rose-100 p-8 text-center relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')]"></div>

                    <div class="relative z-10">
                        <div class="w-24 h-24 bg-white rounded-[1rem] mx-auto flex items-center justify-center shadow-xl shadow-rose-200/50 mb-6 border border-rose-100 relative group">
                            <div class="absolute inset-0 bg-rose-500/10 rounded-[1rem] animate-ping"></div>
                            <i class="pi pi-server text-4xl text-rose-500 group-hover:scale-110 transition-transform"></i>
                            <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-rose-500 rounded-full flex items-center justify-center border-4 border-white shadow-sm">
                                <i class="pi pi-times text-white text-xs font-black"></i>
                            </div>
                        </div>

                        <Tag value="Erreur 503 / 500" class="bg-rose-100 text-rose-700 text-[10px] uppercase font-black tracking-widest px-3 mb-3 border border-rose-200" />
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight">Interruption du Service Analytique</h1>
                        <p class="text-slate-600 font-medium mt-3 max-w-md mx-auto">
                            Le moteur d'agrégation de données a rencontré une difficulté technique lors de la compilation de votre tableau de bord.
                        </p>
                    </div>
                </div>

                <!-- CORPS CARTE (DÉTAILS) -->
                <div class="p-8 bg-white">

                    <!-- Fausse Console Terminal pour le message d'erreur -->
                    <div class="bg-slate-900 rounded-2xl p-5 mb-8 shadow-inner border border-slate-800 relative overflow-hidden group">
                        <div class="flex items-center gap-2 mb-3 border-b border-slate-700/50 pb-3">
                            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="ml-2 text-[10px] font-mono text-slate-500 uppercase tracking-widest">engine_diagnostics.log</span>
                        </div>
                        <p class="font-mono text-sm text-emerald-400 leading-relaxed break-words">
                            <span class="text-slate-500">[{{ new Date().toISOString() }}]</span>
                            <span class="text-rose-400 ml-2">FATAL_EXCEPTION:</span> <br>
                            {{ props.error }}
                        </p>
                    </div>

                    <!-- Progress Bar (Auto retry) -->
                    <div class="mb-8" v-if="!isRetrying && autoRetryCountdown > 0">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Nouvelle tentative dans</span>
                            <span class="text-xs font-black text-indigo-600">{{ autoRetryCountdown }}s</span>
                        </div>
                        <ProgressBar :value="((30 - autoRetryCountdown) / 30) * 100" :showValue="false" class="h-1.5 bg-slate-100" color="#6366F1" />
                    </div>

                    <!-- ACTIONS -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <Button
                            :label="isRetrying ? 'Reconnexion...' : 'Forcer le rechargement'"
                            :icon="isRetrying ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'"
                            class="p-button-primary flex-1 font-black rounded-xl py-3 shadow-lg shadow-indigo-500/30"
                            @click="retryConnection"
                            :disabled="isRetrying"
                        />
                        <Button
                            label="Retour à l'accueil"
                            icon="pi pi-home"
                            class="p-button-outlined p-button-secondary flex-1 font-bold rounded-xl py-3"
                            @click="goBackHome"
                            :disabled="isRetrying"
                        />
                    </div>
                </div>

                <!-- FOOTER CARTE -->
                <div class="bg-slate-50 p-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="pi pi-shield text-slate-400 text-xl"></i>
                        <div class="text-left">
                            <p class="text-xs font-bold text-slate-700">Vos données sont en sécurité</p>
                            <p class="text-[10px] text-slate-500">L'équipe technique a été notifiée de cet incident.</p>
                        </div>
                    </div>
                    <Button
                        label="Contacter le Support IT"
                        icon="pi pi-envelope"
                        class="p-button-text p-button-sm p-button-secondary font-bold text-xs"
                        @click="contactSupport"
                    />
                </div>

            </div>
        </div>

        <!-- FOOTER GLOBAL -->
        <div class="absolute bottom-6 left-0 w-full text-center z-10 pointer-events-none">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">SubnetCongo LMS Enterprise &copy; {{ new Date().getFullYear() }}</p>
        </div>

    </div>
</template>

<style scoped>
/* ==========================================================================
   ANIMATIONS & UTILITIES
   ========================================================================== */

.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-pulse-slow {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-gradient-x {
    background-size: 200% 200%;
    animation: gradientX 3s ease infinite;
}

@keyframes gradientX {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Surcharge de PrimeVue pour ce composant isolé */
:deep(.p-progressbar .p-progressbar-value) {
    transition: width 1s linear;
}
</style>
