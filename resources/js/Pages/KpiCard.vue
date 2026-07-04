<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, required: true },
    colorClass: { type: String, default: 'violet' }
});
</script>

<template>
    <div
        class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group transition-colors cursor-default"
        :class="`hover:border-${colorClass}-300`"
    >
        <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
            <i :class="[icon, `text-9xl text-${colorClass}-900`]"></i>
        </div>
        <div class="relative z-10">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
                <i :class="[icon, `text-${colorClass}-500`]"></i>
                {{ label }}
            </p>
            <h3 class="text-4xl font-black text-slate-800">{{ value }}</h3>
        </div>
    </div>
</template>
