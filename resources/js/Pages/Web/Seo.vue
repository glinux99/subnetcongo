<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="SEO" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto">
                <h1 class="text-3xl font-black text-slate-900">Référencement (SEO)</h1>
                <p class="text-slate-500 mt-2">Optimisez votre visibilité sur les moteurs de recherche.</p>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-8">
                <p class="text-slate-600">La configuration SEO avancée (meta-title, meta-description, sitemap.xml, robots.txt, schema.org) se gère depuis le panneau <strong>Admin → Web → SEO</strong>.</p>
                <p class="text-slate-600 mt-4">Vous pouvez aussi prévisualiser la SERP Google pour la page d'accueil :</p>
                <pre class="mt-4 p-4 bg-slate-100 rounded text-sm">{{ identity.site_name }} — {{ identity.site_tagline }}
{{ identity.site_description }}

{{ identity.contact_address }} • {{ identity.contact_phone }}</pre>
            </div>
        </section>
    </AppLayout>
</template>
