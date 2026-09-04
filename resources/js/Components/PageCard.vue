<script setup>
/**
 * PageCard — Conteneur de carte standardisé
 * Usage :
 *   <PageCard title="Titre" :actions="[...]">
 *     <template #header>...</template>
 *     ...
 *   </PageCard>
 */
defineProps({
    title:    { type: String, default: '' },
    subtitle: { type: String, default: '' },
    padded:   { type: Boolean, default: true },
});
</script>

<template>
    <div class="bg-white rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-md transition-all">
        <div v-if="title || $slots.header" class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 v-if="title" class="text-sm font-black text-slate-800 uppercase tracking-widest italic">{{ title }}</h3>
                <p v-if="subtitle" class="text-[10px] text-slate-500 font-medium italic mt-1">{{ subtitle }}</p>
            </div>
            <div>
                <slot name="header" />
            </div>
        </div>
        <div :class="padded ? 'p-6' : ''">
            <slot />
        </div>
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-[1rem]">
            <slot name="footer" />
        </div>
    </div>
</template>
