<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({ sessions: Array });

const formatDate = (value) => value
    ? new Date(value).toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
    : 'Date à confirmer';
</script>

<template>
    <Head title="Sessions en direct" />

    <div class="min-h-screen bg-slate-50 font-sans">
        <header class="bg-slate-900 text-white">
            <div class="max-w-6xl mx-auto px-4 py-10">
                <p class="text-[10px] font-black uppercase tracking-widest text-white/60 mb-2">Subnet Congo Academy</p>
                <h1 class="text-3xl lg:text-4xl font-black tracking-tight">Sessions en direct</h1>
                <p class="text-white/70 mt-2 max-w-2xl">Participez aux webinaires et classes virtuelles ouverts au public. Connectez-vous pour rejoindre une session.</p>
                <div class="flex gap-2 mt-6">
                    <Link :href="route('login')">
                        <Button label="Se connecter" icon="pi pi-sign-in" />
                    </Link>
                    <Link :href="route('landing')">
                        <Button label="Retour au site" icon="pi pi-arrow-left" outlined severity="secondary" />
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 py-10">
            <div v-if="sessions?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <article v-for="s in sessions" :key="s.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <Tag :value="s.status === 'Live' ? 'En direct' : 'Programmée'" :severity="s.status === 'Live' ? 'danger' : 'info'" :class="s.status === 'Live' ? 'animate-pulse' : ''" />
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Webinaire</span>
                    </div>

                    <h2 class="text-lg font-bold text-slate-900">{{ s.title }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ s.course?.title }}</p>

                    <div class="text-sm text-slate-600 mt-3 space-y-1">
                        <p><i class="pi pi-user text-xs mr-1"></i> {{ s.instructor?.name || 'Formateur' }}</p>
                        <p><i class="pi pi-calendar text-xs mr-1"></i> {{ formatDate(s.start_at) }}</p>
                        <p v-if="s.duration"><i class="pi pi-hourglass text-xs mr-1"></i> {{ s.duration }} min</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <Link :href="s.share_url || route('live-sessions.join', s.uuid)" class="block">
                            <Button :label="s.status === 'Live' ? 'Rejoindre maintenant' : 'S’inscrire / Rejoindre'"
                                    :icon="s.status === 'Live' ? 'pi pi-video' : 'pi pi-ticket'"
                                    :severity="s.status === 'Live' ? 'danger' : 'primary'" class="w-full" />
                        </Link>
                    </div>
                </article>
            </div>

            <div v-else class="text-center py-20">
                <i class="pi pi-desktop text-4xl text-slate-300 mb-3 block"></i>
                <h2 class="text-lg font-bold text-slate-700">Aucune session publique pour le moment</h2>
                <p class="text-sm text-slate-500 mt-1">Revenez bientôt ou consultez nos formations.</p>
            </div>
        </main>
    </div>
</template>
