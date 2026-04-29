<script setup>
import UserForm from '@/Pages/Admin/Users/Partials/UserForm.vue';

defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <UserForm
        title="Modifier un utilisateur"
        subtitle="Mettez a jour le profil et le role associe."
        :user="user"
        :roles="roles"
        :submit-url="route('admin.users.update', user.id)"
        method="patch"
        submit-label="Enregistrer les modifications"
    />
</template>