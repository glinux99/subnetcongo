<script setup>
/**
 * Role/Edit — Dialogue de modification de rôle (design system SubnetCongo).
 * Conservé pour compatibilité ; la gestion CRUD principale se fait dans Role/Index.vue.
 */
import { useForm } from "@inertiajs/vue3";
import { watchEffect, computed } from "vue";
import SncDialog from '@/Components/SncDialog.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    title: String,
    role: Object,
    permissions: Object,
});

const emit = defineEmits(["close"]);

const form = useForm({
    name: "",
    permissions: [],
});

const update = () => {
    form.put(route("role.update", props.role?.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            form.reset();
        },
        onError: () => null,
        onFinish: () => null,
    });
};

watchEffect(() => {
    if (props.show) {
        form.errors = {};
        form.name = props.role?.name;
        form.permissions = props.role.permissions?.map((d) => d.id) ?? [];
    }
});

const allSelected = computed(() =>
    props.permissions.length > 0 && form.permissions.length === props.permissions.length
);

const toggleAll = () => {
    form.permissions = allSelected.value ? [] : props.permissions.map(p => p.id);
};

const onDialogUpdate = (value) => {
    if (!value) emit("close");
};
</script>

<template>
    <SncDialog
        :visible="props.show"
        :title="t('pages.roles.dialog_edit_title')"
        :subtitle="t('pages.roles.dialog_subtitle')"
        icon="pi-pencil"
        icon-class="from-blue-500 to-indigo-600"
        width="760px"
        @update:visible="onDialogUpdate"
    >
        <form id="role-edit-form" @submit.prevent="update">
            <div class="space-y-6">
                <div class="space-y-2">
                    <label for="name" class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ t('pages.roles.field_role_name') }}</label>
                    <InputText id="name" v-model="form.name" class="w-full rounded-xl border-slate-300 font-bold p-3" :placeholder="t('pages.roles.field_role_name_placeholder')" autocomplete="off" autofocus />
                    <small v-if="form.errors.name" class="text-red-500 font-bold">{{ form.errors.name }}</small>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ t('pages.roles.field_permissions') }}</label>
                        <Button :label="allSelected ? t('pages.roles.btn_uncheck_all') : t('pages.roles.btn_check_all')" icon="pi pi-check-square" text severity="secondary" size="small" class="!text-[10px] !font-black !uppercase !tracking-widest" @click="toggleAll" />
                    </div>

                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 max-h-[320px] overflow-y-auto custom-scrollbar">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="permission in props.permissions"
                                :key="permission.id"
                                class="flex items-center gap-2 p-2 rounded-xl border transition-all cursor-pointer select-none"
                                :class="form.permissions.includes(permission.id) ? 'bg-blue-50 border-blue-200' : 'bg-white border-slate-200 hover:border-slate-300'"
                                @click="form.permissions.includes(permission.id) ? form.permissions = form.permissions.filter(id => id !== permission.id) : form.permissions.push(permission.id)"
                            >
                                <Checkbox v-model="form.permissions" :inputId="'permission_' + permission.id" :value="permission.id" @click.stop />
                                <label :for="'permission_' + permission.id" class="text-xs font-bold text-slate-700 cursor-pointer">{{ permission.name }}</label>
                            </div>
                        </div>
                    </div>

                    <small v-if="form.errors.permissions" class="text-red-500 font-bold">{{ form.errors.permissions }}</small>
                </div>
            </div>
        </form>

        <template #footer>
            <Button :label="t('pages.roles.btn_cancel')" icon="pi pi-times" text severity="secondary" @click="emit('close')" />
            <Button type="submit" form="role-edit-form" :label="t('pages.roles.btn_update_role')" icon="pi pi-save" severity="primary" class="px-8 font-black" :loading="form.processing" />
        </template>
    </SncDialog>
</template>
