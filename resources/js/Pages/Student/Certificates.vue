<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    certificates: Array,
    meta: Object
});

const toast = useToast();

const PLACEHOLDER = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=400';

const getImageUrl = (path) => {
    if (!path) return PLACEHOLDER;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const handleImageError = (e) => { e.target.src = PLACEHOLDER; };

const certs = computed(() => props.certificates ?? []);
const bestScore = computed(() => certs.value.reduce((m, c) => Math.max(m, Math.round(Number(c.score) || 0)), 0));
const latest = computed(() => certs.value[0]?.issued_at ?? '—');

const download = (cert) => {
    window.open(cert.download_url, '_blank', 'noopener');
    toast.add({ severity: 'success', summary: 'Téléchargement lancé', detail: 'Votre certificat PDF s\'ouvre dans un nouvel onglet.', life: 3000 });
};

const copyVerifyLink = async (cert) => {
    try {
        await navigator.clipboard.writeText(cert.verify_url);
        toast.add({ severity: 'info', summary: 'Lien copié', detail: 'Partagez ce lien pour permettre la vérification du certificat.', life: 2500 });
    } catch (_) {
        toast.add({ severity: 'warn', summary: 'Copie impossible', detail: cert.verify_url, life: 6000 });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Mes certificats" />

        <div class="page-bg min-h-screen">
            <PageHeader
                title="Mes certificats"
                icon="pi-verified"
                accent="emerald"
                badge="Certificats vérifiables"
                subtitle="Vos réussites officielles. Chaque certificat possède un code unique que vos employeurs peuvent vérifier en ligne."
            />

            <div class="page-container space-y-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <PageKpi label="Certificats obtenus" :value="meta?.total ?? certs.length" icon="pi-verified" color="emerald" />
                    <PageKpi label="Score moyen" :value="`${meta?.avg_score ?? 0} %`" icon="pi-chart-bar" color="blue" />
                    <PageKpi label="Meilleur score" :value="`${bestScore} %`" icon="pi-star" color="amber" />
                    <PageKpi label="Dernier obtenu" :value="latest" icon="pi-calendar" color="slate" />
                </div>

                <div v-if="certs.length === 0" class="page-card-flush">
                    <EmptyState
                        icon="pi-verified"
                        title="Votre premier certificat vous attend"
                        description="Terminez toutes les leçons d'une formation et réussissez l'examen final pour obtenir un certificat vérifiable."
                        action-label="Voir mes formations"
                        :action-href="route('student.my-courses')"
                    />
                </div>

                <ul v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6" aria-label="Liste de mes certificats">
                    <li v-for="cert in certs" :key="cert.id" class="group relative bg-[#0F172A] rounded-2xl overflow-hidden shadow-xl ring-1 ring-white/5 hover:-translate-y-1 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/20 to-transparent pointer-events-none" aria-hidden="true"></div>
                        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full flex items-center justify-center rotate-12 pointer-events-none border border-white/5" aria-hidden="true">
                            <i class="pi pi-verified text-white/10 text-6xl"></i>
                        </div>

                        <div class="p-6 md:p-8 relative z-10 flex flex-col h-full gap-6">
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex gap-4 items-center min-w-0">
                                    <div class="w-14 h-14 shrink-0 bg-white rounded-xl overflow-hidden ring-2 ring-emerald-500/30 p-0.5">
                                        <img :src="getImageUrl(cert.course_image)" alt="" loading="lazy" @error="handleImageError" class="w-full h-full object-cover rounded-[0.6rem]" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[0.6875rem] font-bold text-emerald-400 uppercase tracking-wider mb-1">Certificat SubnetCongo</p>
                                        <h3 class="text-lg font-bold text-white leading-snug line-clamp-2">{{ cert.course_title }}</h3>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-3xl font-extrabold text-emerald-400 tabular-nums">{{ Math.round(cert.score) }} %</p>
                                    <p class="text-[0.6875rem] font-semibold text-slate-400 uppercase tracking-wider">Score</p>
                                </div>
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center gap-2 text-slate-400">
                                    <i class="pi pi-calendar text-emerald-500" aria-hidden="true"></i>
                                    <dt class="sr-only">Date de délivrance</dt>
                                    <dd>Délivré le <span class="text-white">{{ cert.issued_at }}</span></dd>
                                </div>
                                <div class="flex items-center gap-2 text-slate-400 min-w-0">
                                    <i class="pi pi-key text-emerald-500" aria-hidden="true"></i>
                                    <dt class="sr-only">Code du certificat</dt>
                                    <dd class="truncate">Code <span class="text-white font-mono">{{ cert.certificate_code }}</span></dd>
                                </div>
                            </dl>

                            <div class="mt-auto flex gap-3">
                                <Button label="Télécharger le PDF" icon="pi pi-download" severity="success" class="flex-1" @click="download(cert)" />
                                <Button
                                    icon="pi pi-link"
                                    outlined
                                    class="!border-white/20 !text-white hover:!bg-white/10"
                                    aria-label="Copier le lien de vérification"
                                    v-tooltip.top="'Copier le lien de vérification'"
                                    @click="copyVerifyLink(cert)"
                                />
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée façon macOS / SaaS Moderne */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* Animations d'entrée (Fade In) */
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Customisation PrimeVue DataTable B2B */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Timeline Custom (Audit Log) */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    width: 100%;
}
:deep(.custom-timeline .p-timeline-event-separator) {
    margin-right: 1rem;
}

/* Overrides UI Forms */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-dropdown:not(.p-disabled).p-focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
}

/* Override Editor Toolbar */
:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
    background-color: #f8fafc;
    border-color: #e2e8f0;
}
:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
    border-color: #e2e8f0;
}
</style>