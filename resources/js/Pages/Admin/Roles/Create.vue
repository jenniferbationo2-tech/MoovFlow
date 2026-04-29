<script setup>
import RoleForm from '@/Pages/Admin/Roles/Partials/RoleForm.vue';

defineProps({
    permissionGroups: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <RoleForm
        title="Creer un role"
        subtitle="Definition d un nouveau role et de sa matrice de permissions."
        :permission-groups="permissionGroups"
        :submit-url="route('admin.roles.store')"
        submit-label="Creer le role"
    />
</template>