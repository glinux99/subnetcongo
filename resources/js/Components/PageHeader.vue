<script setup>
/**
 * PageHeader — En-tête de page unifié (hero sombre).
 * Usage : <PageHeader title="..." subtitle="..." badge="..." icon="pi-book" accent="blue" :actions="[...]" />
 * actions : [{ label, icon, href?, onClick?, variant: 'primary' | 'secondary', ariaLabel? }]
 *   - avec `href` : rendu en lien Inertia
 *   - sans `href` : rendu en <button> appelant `onClick`
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
    badge:    { type: String, default: '' },
    icon:     { type: String, default: 'pi-th-large' },
    accent:   { type: String, default: 'blue' },
    actions:  { type: Array, default: () => [] },
});

const accentMap = {
    blue:    { icon: 'bg-blue-600',    badge: 'bg-blue-500/10 text-blue-300 border-blue-400/30' },
    red:     { icon: 'bg-red-600',     badge: 'bg-red-500/10 text-red-300 border-red-400/30' },
    indigo:  { icon: 'bg-indigo-600',  badge: 'bg-indigo-500/10 text-indigo-300 border-indigo-400/30' },
    violet:  { icon: 'bg-violet-600',  badge: 'bg-violet-500/10 text-violet-300 border-violet-400/30' },
    emerald: { icon: 'bg-emerald-600', badge: 'bg-emerald-500/10 text-emerald-300 border-emerald-400/30' },
    amber:   { icon: 'bg-amber-500',   badge: 'bg-amber-500/10 text-amber-300 border-amber-400/30' },
    slate:   { icon: 'bg-slate-600',   badge: 'bg-slate-500/10 text-slate-300 border-slate-400/30' },
};
</script>

<template>
    <header class="page-header">
        <div class="page-header-orb-1" aria-hidden="true"></div>
        <div class="page-header-orb-2" aria-hidden="true"></div>

        <div class="page-header-content">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="page-header-icon" :class="(accentMap[accent] || accentMap.blue).icon" aria-hidden="true">
                    <i :class="['pi', icon, 'text-white text-2xl']"></i>
                </div>
                <div class="min-w-0">
                    <span v-if="badge" class="page-header-badge mb-3" :class="(accentMap[accent] || accentMap.blue).badge">{{ badge }}</span>
                    <h1 class="page-header-title">{{ title }}</h1>
                    <p v-if="subtitle" class="page-header-subtitle">{{ subtitle }}</p>
                    <slot name="meta" />
                </div>
            </div>

            <div v-if="actions.length || $slots.actions" class="flex flex-wrap gap-3 w-full md:w-auto">
                <template v-for="(action, idx) in actions" :key="idx">
                    <Link
                        v-if="action.href"
                        :href="action.href"
                        :aria-label="action.ariaLabel || undefined"
                        :class="action.variant === 'primary' ? 'btn-header-primary' : 'btn-header-secondary'"
                    >
                        <i v-if="action.icon" :class="['pi', action.icon]" aria-hidden="true"></i>
                        {{ action.label }}
                    </Link>
                    <button
                        v-else
                        type="button"
                        :aria-label="action.ariaLabel || undefined"
                        :disabled="action.disabled"
                        :class="[action.variant === 'primary' ? 'btn-header-primary' : 'btn-header-secondary', 'disabled:opacity-50 disabled:cursor-not-allowed']"
                        @click="action.onClick && action.onClick()"
                    >
                        <i v-if="action.icon" :class="['pi', action.icon]" aria-hidden="true"></i>
                        {{ action.label }}
                    </button>
                </template>
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
