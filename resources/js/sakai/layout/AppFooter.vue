<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAppVersion } from '@/composables/useAppVersion';

const page = usePage();
const appMeta = computed(() => page.props.app_meta || {});
const identity = computed(() => page.props.identity || {});
const year = new Date().getFullYear();
const siteName = computed(() => identity.value.site_name || appMeta.value.name || 'SubnetCongo');
const { label: versionLabel } = useAppVersion();
</script>

<template>
    <footer class="mt-10 flex flex-col items-center justify-between gap-2 rounded-2xl border border-slate-100 bg-white/60 p-4 text-center text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-500 sm:flex-row sm:text-left md:p-5">
        <div class="flex min-w-0 flex-wrap items-center justify-center gap-3 sm:justify-start">
            <span>© {{ year }} {{ siteName }}</span>
            <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700" aria-hidden="true"></span>
            <span class="text-slate-500 dark:text-slate-400">SubnetCongo Academy</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-slate-400 dark:text-slate-500">{{ versionLabel }}</span>
        </div>
    </footer>
</template>
