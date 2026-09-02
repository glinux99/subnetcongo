<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] }, // [{ label, to?, icon? }]
});

const page = usePage();

// Auto-derive a sensible trail when items are not provided
const trail = computed(() => {
    if (props.items && props.items.length > 0) return props.items;
    const path = page.url.split('?')[0];
    const segments = path.split('/').filter(Boolean);
    const acc = [{ label: 'Accueil', to: route('dashboard'), icon: 'pi pi-home' }];
    let url = '';
    segments.forEach((seg, idx) => {
        url += '/' + seg;
        const label = seg.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        acc.push({ label, to: idx === segments.length - 1 ? null : url });
    });
    return acc;
});
</script>

<template>
    <nav class="flex items-center gap-1.5 text-xs font-bold" aria-label="Fil d'Ariane">
        <template v-for="(item, idx) in trail" :key="idx">
            <i v-if="item.icon" :class="item.icon" class="text-slate-400 text-xs"></i>
            <Link v-if="item.to && idx < trail.length - 1"
                  :href="item.to"
                  class="text-slate-500 hover:text-violet-600 transition-colors capitalize">
                {{ item.label }}
            </Link>
            <span v-else class="text-slate-800 capitalize">{{ item.label }}</span>
            <i v-if="idx < trail.length - 1" class="pi pi-angle-right text-slate-300 text-[10px] mx-1"></i>
        </template>
    </nav>
</template>