<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});

const destroyRole = (roleId) => {
    router.delete(route('admin.roles.destroy', roleId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Administration des roles" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Gestion des roles</h1>
                    <p class="mt-1 text-sm text-slate-500">Catalogue des roles avec permissions et nombre d utilisateurs.</p>
                </div>

                <Link :href="route('admin.roles.create')" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]">
                    Creer role
                </Link>
            </div>
        </template>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="space-y-4">
                <article v-for="role in roles" :key="role.id" class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">{{ role.name }}</h2>
                                <p class="text-sm text-slate-500">{{ role.users_count }} utilisateur(s) assignes</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="permission in role.permissions" :key="permission" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ permission }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Link :href="route('admin.roles.edit', role.id)" class="rounded-xl bg-[#0066B3]/10 px-3 py-2 text-xs font-semibold text-[#0066B3] transition hover:bg-[#0066B3]/20">
                                Editer
                            </Link>
                            <button type="button" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100" @click="destroyRole(role.id)">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </article>

                <p v-if="roles.length === 0" class="text-sm text-slate-500">Aucun role disponible.</p>
            </div>
        </div>
    </AppLayout>
</template>