<script setup>
/**
 * SncAvatar — Avatar standardisé du design system SUBNETCONGO.
 * Usage : <SncAvatar src="..." name="John Doe" size="md" />
 */
import { computed } from 'vue';

const props = defineProps({
    src: { type: String, default: '' },
    name: { type: String, default: '' },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v)
    },
    status: {
        type: String,
        default: '',
        validator: (v) => ['', 'online', 'offline', 'busy', 'away'].includes(v)
    },
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'w-6 h-6 text-[10px]',
        sm: 'w-8 h-8 text-xs',
        md: 'w-10 h-10 text-sm',
        lg: 'w-12 h-12 text-base',
        xl: 'w-16 h-16 text-lg',
    };
    return sizes[props.size];
});

const initials = computed(() => {
    if (!props.name) return '';
    return props.name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const bgColor = computed(() => {
    const colors = [
        'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
        'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-pink-500'
    ];
    const index = props.name.charCodeAt(0) % colors.length;
    return colors[index];
});

const statusColor = computed(() => {
    const colors = {
        online: 'bg-emerald-500',
        offline: 'bg-slate-400',
        busy: 'bg-red-500',
        away: 'bg-amber-500',
    };
    return colors[props.status] || '';
});
</script>

<template>
    <div class="relative inline-flex items-center justify-center rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700" :class="sizeClasses">
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="w-full h-full object-cover"
            @error="$event.target.style.display = 'none'"
        />
        <span
            v-else-if="initials"
            :class="[bgColor, 'w-full h-full flex items-center justify-center text-white font-bold']"
        >
            {{ initials }}
        </span>
        <span
            v-if="status"
            :class="['absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-slate-900', statusColor]"
            :aria-label="status"
        />
    </div>
</template>
