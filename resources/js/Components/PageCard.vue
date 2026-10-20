<script setup>
/**
 * PageCard — Conteneur de carte standardisé (clair / sombre).
 * Usage :
 *   <PageCard title="Titre" subtitle="...">
 *     <template #header>actions</template>
 *     contenu
 *     <template #footer>...</template>
 *   </PageCard>
 */
defineProps({
    title:    { type: String, default: '' },
    subtitle: { type: String, default: '' },
    padded:   { type: Boolean, default: true },
    as:       { type: String, default: 'section' },
});
</script>

<template>
    <component :is="as" class="page-card-flush">
        <div v-if="title || $slots.header" class="page-card-header flex-wrap">
            <div class="min-w-0">
                <h2 v-if="title" class="page-card-title">{{ title }}</h2>
                <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.header" class="flex items-center gap-2">
                <slot name="header" />
            </div>
        </div>
        <div :class="padded ? 'page-card-body' : ''">
            <slot />
        </div>
        <div v-if="$slots.footer" class="page-card-footer">
            <slot name="footer" />
        </div>
    </component>
</template>
