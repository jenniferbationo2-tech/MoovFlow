<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    presences: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            presents: 0,
            absents: 0,
            taux: 0,
        }),
    },
});

const columns = [
    { key: 'participant', label: 'Participant' },
    { key: 'email', label: 'Email' },
    { key: 'statut', label: 'Statut' },
    { key: 'scan_time', label: 'Heure scan' },
];

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Non scanne';
</script>

<template>
    <Head :title="`Presences - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Presences</h1>
                <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }}</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Presents" :value="stats.presents" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="Absents" :value="stats.absents" color="slate" icon="M6 18 18 6M6 6l12 12" />
                <StatCard label="Taux presence" :value="`${stats.taux}%`" color="blue" icon="M4 16l5-5 4 4 7-8" />
            </div>

            <DataTable :columns="columns" :rows="presences" empty-message="Aucune presence enregistree.">
                <template #cell-statut="{ row }">
                    <StatusBadge :status="row.statut" />
                </template>
                <template #cell-scan_time="{ row }">
                    <span>{{ formatDate(row.scan_time) }}</span>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>