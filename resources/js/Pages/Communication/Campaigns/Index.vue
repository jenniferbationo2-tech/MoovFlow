<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    campaigns: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            envoyees: 0,
            ouvertes: 0,
            acceptees: 0,
        }),
    },
});

const columns = [
    { key: 'objet', label: 'Nom campagne' },
    { key: 'date_envoi', label: 'Date envoi' },
    { key: 'nb_destinataires', label: 'Destinataires' },
    { key: 'taux_acceptation', label: 'Taux acceptation' },
    { key: 'statut', label: 'Statut' },
];

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Non planifiée';
</script>

<template>
    <Head :title="`Campagnes - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Communication · Campagnes email</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · suivi des invitations et performances d’envoi.</p>
                </div>

                <Link
                    :href="route('communication.campaigns.create', evenement.id)"
                    class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                >
                    Nouvelle campagne
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Campagnes envoyées" :value="stats.envoyees" color="blue" icon="M5 5h14v14H5z" />
                <StatCard label="Ouvertures simulées" :value="stats.ouvertes" color="green" icon="M3 12s3-6 9-6 9 6 9 6-3 6-9 6-9-6-9-6Z" />
                <StatCard label="Invitations acceptées" :value="stats.acceptees" color="slate" icon="M5 13l4 4L19 7" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Historique des campagnes</h2>
                        <p class="mt-1 text-sm text-slate-500">Chaque campagne centralise les envois, ouvertures et taux d’acceptation.</p>
                    </div>
                </div>

                <DataTable :columns="columns" :rows="campaigns" empty-message="Aucune campagne n’a encore été envoyée.">
                    <template #cell-objet="{ row }">
                        <div>
                            <p class="font-semibold text-slate-900">{{ row.objet }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.nb_ouvertures }} ouvertures · {{ row.nb_acceptations }} acceptations</p>
                        </div>
                    </template>

                    <template #cell-date_envoi="{ row }">
                        <span>{{ formatDate(row.date_envoi) }}</span>
                    </template>

                    <template #cell-taux_acceptation="{ row }">
                        <span class="font-semibold text-slate-900">{{ row.taux_acceptation }}%</span>
                    </template>

                    <template #cell-statut="{ row }">
                        <StatusBadge :status="row.statut === 'planifiee' ? 'attente' : 'publie'" />
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>