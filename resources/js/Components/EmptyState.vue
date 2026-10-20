<script setup>
/**
 * EmptyState — État vide : icône, titre, explication, action.
 * Usage : <EmptyState icon="pi-book" title="Aucune formation" description="..." action-label="Voir le catalogue" :action-href="route('...')" />
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    icon:        { type: String, default: 'pi-inbox' },
    title:       { type: String, required: true },
    description: { type: String, default: '' },
    actionLabel: { type: String, default: '' },
    actionHref:  { type: String, default: '' },
    actionIcon:  { type: String, default: 'pi-arrow-right' },
    compact:     { type: Boolean, default: false },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div :class="['flex flex-col items-center justify-center text-center', compact ? 'py-8 px-4' : 'py-16 px-6']" role="status">
        <div class="relative mb-5" aria-hidden="true">
            <div class="absolute inset-0 rounded-full bg-blue-500/10 blur-xl"></div>
            <div :class="['relative rounded-2xl flex items-center justify-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700', compact ? 'w-14 h-14' : 'w-20 h-20']">
                <i :class="['pi', icon, 'text-slate-400 dark:text-slate-500', compact ? 'text-xl' : 'text-3xl']"></i>
            </div>
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">{{ title }}</h3>
        <p v-if="description" class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 max-w-md leading-relaxed">{{ description }}</p>
        <div v-if="actionLabel || $slots.default" class="mt-6 flex flex-wrap gap-3 justify-center">
            <Link v-if="actionLabel && actionHref" :href="actionHref" class="btn-action-primary">
                {{ actionLabel }}
                <i :class="['pi', actionIcon, 'text-xs']" aria-hidden="true"></i>
            </Link>
            <button v-else-if="actionLabel" type="button" class="btn-action-primary" @click="emit('action')">
                {{ actionLabel }}
                <i :class="['pi', actionIcon, 'text-xs']" aria-hidden="true"></i>
            </button>
            <slot />
        </div>
    </div>
</template>
