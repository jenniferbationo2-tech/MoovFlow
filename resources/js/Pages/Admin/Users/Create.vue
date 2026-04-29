<script setup>
import UserForm from '@/Pages/Admin/Users/Partials/UserForm.vue';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <UserForm
        title="Creer un utilisateur"
        subtitle="Renseignez les informations essentielles et attribuez un role."
        :roles="roles"
        :submit-url="route('admin.users.store')"
        submit-label="Creer l utilisateur"
    />
</template>