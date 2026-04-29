<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    roles: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_users: 0,
            active_users: 0,
            inactive_users: 0,
            by_role: [],
        }),
    },
});

const columns = [
    { key: 'name', label: 'Utilisateur' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Statut' },
    { key: 'created_at', label: 'Creation' },
    { key: 'actions', label: 'Actions' },
];

const localFilters = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.users.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';

const toggleActive = (user) => {
    router.patch(route('admin.users.toggle', user.id), {}, {
        preserveScroll: true,
    });
};

const deactivate = (user) => {
    router.delete(route('admin.users.destroy', user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Administration utilisateurs" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Gestion des utilisateurs</h1>
                    <p class="mt-1 text-sm text-slate-500">Administration des comptes, roles et statuts d activation.</p>
                </div>

                <Link :href="route('admin.users.create')" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]">
                    Creer utilisateur
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Total utilisateurs" :value="stats.total_users" color="blue" icon="M17 20h5V4H2v16h5M9 20h6M12 16v4M7 8h10M7 12h6" />
                <StatCard label="Comptes actifs" :value="stats.active_users" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="Comptes inactifs" :value="stats.inactive_users" color="slate" icon="M6 18L18 6M6 6l12 12" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                        <input v-model="localFilters.search" type="text" placeholder="Nom, prenom, email, telephone" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Role</label>
                        <select v-model="localFilters.role" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les roles</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="localFilters.status" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Repartition par role</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    <div v-for="roleStat in stats.by_role" :key="roleStat.name" class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ roleStat.name }}</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ roleStat.total }}</p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns" :rows="users.data" :pagination="users" empty-message="Aucun utilisateur ne correspond aux filtres.">
                <template #cell-name="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.telephone || 'Telephone non renseigne' }}</p>
                    </div>
                </template>

                <template #cell-roles="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <span v-for="role in row.roles" :key="role" class="rounded-full bg-[#0066B3]/10 px-3 py-1 text-xs font-semibold text-[#0066B3]">
                            {{ role }}
                        </span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span :class="row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'" class="rounded-full px-3 py-1 text-xs font-semibold">
                        {{ row.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </template>

                <template #cell-created_at="{ row }">
                    <span>{{ formatDate(row.created_at) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('admin.users.show', row.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                            Voir
                        </Link>
                        <Link :href="route('admin.users.edit', row.id)" class="rounded-xl bg-[#0066B3]/10 px-3 py-2 text-xs font-semibold text-[#0066B3] transition hover:bg-[#0066B3]/20">
                            Editer
                        </Link>
                        <button type="button" class="rounded-xl bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-200" @click="toggleActive(row)">
                            {{ row.is_active ? 'Desactiver' : 'Activer' }}
                        </button>
                        <button type="button" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100" @click="deactivate(row)">
                            Desactiver
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>