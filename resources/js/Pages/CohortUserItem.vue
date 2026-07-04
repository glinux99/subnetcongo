<script setup>
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';

const props = defineProps({
    user: { type: Object, required: true },
    type: { type: String, default: 'available' } // 'available' ou 'enrolled'
});

const emit = defineEmits(['add', 'remove']);
</script>

<template>
    <div
        class="bg-white p-3 rounded-xl border shadow-sm flex items-center gap-3 transition-colors group"
        :class="user.is_staged ? 'border-amber-300 bg-amber-50/30' : 'border-slate-200 hover:border-blue-300'"
    >
        <Avatar :image="user.avatar || `https://ui-avatars.com/api/?name=${user.name}`" shape="circle" class="flex-shrink-0" />
        <div class="flex-1 min-w-0">
            <div class="font-bold text-slate-800 text-sm truncate">{{ user.name }}</div>
            <div v-if="type === 'available'" class="text-[10px] text-slate-500 truncate">{{ user.email }}</div>
            <div v-else class="flex items-center gap-2 mt-0.5">
                <Tag v-if="user.is_staged" value="NON SAUVEGARDÉ" severity="warning" class="text-[8px]" />
                <span v-else class="text-[10px] text-slate-500">Inscrit le {{ user.enrolled_at }}</span>
            </div>
        </div>

        <Button
            v-if="type === 'available'"
            icon="pi pi-plus"
            class="p-button-rounded p-button-text p-button-info opacity-50 group-hover:opacity-100 transition-opacity bg-blue-50 flex-shrink-0 w-8 h-8"
            @click="emit('add', user)" v-tooltip.top="'Ajouter à la cohorte'"
        />
        <Button
            v-else
            icon="pi pi-times"
            class="p-button-rounded p-button-text p-button-danger opacity-50 group-hover:opacity-100 hover:bg-rose-50 flex-shrink-0 w-8 h-8"
            @click="emit('remove', user)" v-tooltip.top="'Retirer'"
        />
    </div>
</template>
