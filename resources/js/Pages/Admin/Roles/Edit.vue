<script setup>
import RoleForm from '@/Pages/Admin/Roles/Partials/RoleForm.vue';

defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissionGroups: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <RoleForm
        title="Modifier un role"
        subtitle="Mise a jour des permissions et des habilitations associees."
        :role="role"
        :permission-groups="permissionGroups"
        :submit-url="route('admin.roles.update', role.id)"
        method="patch"
        submit-label="Enregistrer les modifications"
    />
</template>