<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    participant: {
        type: Object,
        required: true,
    },
});

const columns = [
    { key: 'evenement', label: 'Evenement' },
    { key: 'date_evenement', label: 'Date' },
    { key: 'tarif', label: 'Tarif' },
    { key: 'statut', label: 'Inscription' },
    { key: 'paiement_statut', label: 'Paiement' },
    { key: 'presence', label: 'Presence' },
];

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';
</script>

<template>
    <Head :title="participant.name" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ participant.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Profil participant et statistiques personnelles.</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Email</p>
                        <p class="mt-2 text-sm text-slate-900">{{ participant.email }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Telephone</p>
                        <p class="mt-2 text-sm text-slate-900">{{ participant.telephone || 'Non renseigne' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Participant</p>
                        <p class="mt-2 text-sm text-slate-900">Historique complet disponible</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <StatCard label="Inscriptions" :value="participant.stats.total_inscriptions" color="blue" icon="M4 6h16M4 12h16M4 18h12" />
                <StatCard label="Presences" :value="participant.stats.total_presences" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="Paiements" :value="participant.stats.total_paiements" color="slate" icon="M12 8c-2 0-3 1-3 2.5S10 13 12 13s3 1 3 2.5S14 18 12 18" />
                <StatCard label="Taux presence" :value="`${participant.stats.taux_presence}%`" color="green" icon="M4 16l5-5 4 4 7-8" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Historique evenements</h2>
                <div class="mt-4">
                    <DataTable :columns="columns" :rows="participant.historique" empty-message="Aucun historique disponible.">
                        <template #cell-date_evenement="{ row }">
                            <span>{{ formatDate(row.date_evenement) }}</span>
                        </template>
                        <template #cell-statut="{ row }">
                            <StatusBadge :status="row.statut" />
                        </template>
                        <template #cell-paiement_statut="{ row }">
                            <StatusBadge :status="row.paiement_statut" />
                        </template>
                        <template #cell-presence="{ row }">
                            <StatusBadge :status="row.presence" />
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>