<script setup>
import DataTable from '@/Components/DataTable.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    activities: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const columns = [
    { key: 'created_at', label: 'Date' },
    { key: 'causer', label: 'Utilisateur' },
    { key: 'action', label: 'Action' },
    { key: 'subject', label: 'Sujet' },
    { key: 'details', label: 'Details' },
];

const localFilters = reactive({
    user: props.filters.user ?? '',
    type: props.filters.type ?? '',
    date: props.filters.date ?? '',
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.audit.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';
</script>

<template>
    <Head title="Journal d audit" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Journal d audit</h1>
                <p class="mt-1 text-sm text-slate-500">Traçabilite chronologique des actions sensibles du systeme.</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Utilisateur</label>
                        <select v-model="localFilters.user" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <input v-model="localFilters.type" type="text" placeholder="log_name ou event" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date</label>
                        <input v-model="localFilters.date" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
                <div>
                    <DataTable :columns="columns" :rows="activities.data" :pagination="activities" empty-message="Aucune entree d audit pour ces filtres.">
                        <template #cell-created_at="{ row }">
                            <span>{{ formatDate(row.created_at) }}</span>
                        </template>
                        <template #cell-causer="{ row }">
                            <span>{{ row.causer?.name || 'Systeme' }}</span>
                        </template>
                        <template #cell-action="{ row }">
                            <div>
                                <p class="font-semibold text-slate-900">{{ row.description }}</p>
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ row.log_name || row.event || 'audit' }}</p>
                            </div>
                        </template>
                        <template #cell-subject="{ row }">
                            <span>{{ row.subject_type || 'Aucun sujet' }}</span>
                        </template>
                        <template #cell-details="{ row }">
                            <Link :href="route('admin.audit.show', row.id)" class="text-sm font-semibold text-[#0066B3] hover:underline">
                                Voir detail
                            </Link>
                        </template>
                    </DataTable>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Timeline visuelle</h2>
                    <div class="mt-6 space-y-4">
                        <div v-for="activity in activities.data" :key="activity.id" class="relative pl-8">
                            <span class="absolute left-2 top-1 h-full w-px bg-slate-200" />
                            <span class="absolute left-0 top-1.5 h-4 w-4 rounded-full bg-[#0066B3]" />
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-semibold text-slate-900">{{ activity.description }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ activity.causer?.name || 'Systeme' }} · {{ formatDate(activity.created_at) }}</p>
                                <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">{{ activity.log_name || activity.event || 'audit' }}</p>
                            </div>
                        </div>
                        <p v-if="activities.data.length === 0" class="text-sm text-slate-500">Aucune activite a afficher.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>