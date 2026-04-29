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
    enquetes: {
        type: Array,
        default: () => [],
    },
});

const columns = [
    { key: 'titre', label: 'Titre' },
    { key: 'type', label: 'Type' },
    { key: 'reponses_count', label: 'Nb réponses' },
    { key: 'statut', label: 'Statut' },
    { key: 'actions', label: 'Actions' },
];
</script>

<template>
    <Head :title="`Enquêtes - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Communication · Enquêtes et sondages</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · création et pilotage des questionnaires.</p>
                </div>

                <Link
                    :href="route('communication.enquetes.create', evenement.id)"
                    class="rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]"
                >
                    Créer enquête
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Enquêtes créées" :value="enquetes.length" color="blue" icon="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                <StatCard label="Participants ciblés" :value="evenement.participants_count" color="green" icon="M16 14a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0" />
                <StatCard label="Réponses collectées" :value="enquetes.reduce((sum, item) => sum + item.reponses_count, 0)" color="slate" icon="M5 13l4 4L19 7" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <DataTable :columns="columns" :rows="enquetes" empty-message="Aucune enquête n’a encore été configurée.">
                    <template #cell-titre="{ row }">
                        <div>
                            <p class="font-semibold text-slate-900">{{ row.titre }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.questions_count }} question(s) · taux de réponse {{ row.taux_reponse }}%</p>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ row.type }}</span>
                    </template>

                    <template #cell-statut="{ row }">
                        <StatusBadge :status="row.statut" />
                    </template>

                    <template #cell-actions="{ row }">
                        <Link
                            :href="route('communication.enquetes.show', { evenement: evenement.id, enquete: row.id })"
                            class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                        >
                            Voir détails
                        </Link>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>