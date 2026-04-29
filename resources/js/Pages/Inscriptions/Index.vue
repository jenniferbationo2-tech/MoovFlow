<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    inscriptions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    evenements: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            payes: 0,
            attente: 0,
            conversion: 0,
        }),
    },
});

const columns = [
    { key: 'participant', label: 'Participant' },
    { key: 'evenement', label: 'Evenement' },
    { key: 'tarif', label: 'Tarif' },
    { key: 'statut', label: 'Statut inscription' },
    { key: 'paiement', label: 'Statut paiement' },
    { key: 'qr_code', label: 'QR code' },
    { key: 'date', label: 'Date inscription', sortable: true },
    { key: 'actions', label: 'Actions' },
];

const localFilters = reactive({
    search: props.filters.search ?? '',
    evenement: props.filters.evenement ?? '',
    statut: props.filters.statut ?? '',
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('inscriptions.index'), localFilters, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const rows = computed(() => props.inscriptions.data);

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';

const destroyInscription = (id) => {
    router.delete(route('inscriptions.destroy', id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Inscriptions" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Inscriptions</h1>
                    <p class="mt-1 text-sm text-slate-500">Suivi des participants, paiements et QR codes.</p>
                </div>

                <div class="flex gap-3">
                    <a
                        v-if="localFilters.evenement"
                        :href="route('evenements.export-participants', localFilters.evenement)"
                        class="inline-flex items-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Export Excel
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard label="Total inscrits" :value="stats.total" color="blue" icon="M4 6h16M4 12h16M4 18h10" />
                <StatCard label="Payes" :value="stats.payes" color="green" icon="M12 8c-2 0-3 1-3 2.5S10 13 12 13s3 1 3 2.5S14 18 12 18m0-12v2m0 10v2" />
                <StatCard label="En attente" :value="stats.attente" color="slate" icon="M12 8v4l3 3M12 21a9 9 0 1 0 0-18a9 9 0 0 0 0 18Z" />
                <StatCard label="Taux conversion" :value="`${stats.conversion}%`" color="green" icon="M4 16l5-5 4 4 7-8" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                        <input v-model="localFilters.search" type="text" placeholder="Nom ou email" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Evenement</label>
                        <select v-model="localFilters.evenement" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les evenements</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="localFilters.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les statuts</option>
                            <option v-for="statut in statuts" :key="statut.value" :value="statut.value">{{ statut.label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns" :rows="rows" :pagination="inscriptions" empty-message="Aucune inscription trouvee.">
                <template #cell-participant="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.participant.name }}</p>
                        <p class="text-xs text-slate-500">{{ row.participant.email }}</p>
                    </div>
                </template>

                <template #cell-evenement="{ row }">
                    <span class="font-medium text-slate-800">{{ row.evenement.titre }}</span>
                </template>

                <template #cell-tarif="{ row }">
                    <div>
                        <p class="font-medium text-slate-800">{{ row.tarif.nom }}</p>
                        <p class="text-xs text-slate-500">{{ row.tarif.montant }} XOF</p>
                    </div>
                </template>

                <template #cell-statut="{ row }">
                    <StatusBadge :status="row.statut" />
                </template>

                <template #cell-paiement="{ row }">
                    <StatusBadge :status="row.paiement_statut" />
                </template>

                <template #cell-qr_code="{ row }">
                    <a v-if="row.qr_code_url" :href="row.qr_code_url" target="_blank" class="text-sm font-semibold text-[#0066B3] hover:underline">
                        Voir QR
                    </a>
                    <span v-else class="text-sm text-slate-400">Indisponible</span>
                </template>

                <template #cell-date="{ row }">
                    <span>{{ formatDate(row.date_inscription) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('inscriptions.show', row.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                            Voir detail
                        </Link>
                        <a v-if="row.qr_code_url" :href="row.qr_code_url" target="_blank" class="rounded-xl bg-[#0066B3]/10 px-3 py-2 text-xs font-semibold text-[#0066B3] hover:bg-[#0066B3]/20">
                            Telecharger QR
                        </a>
                        <button type="button" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100" @click="destroyInscription(row.id)">
                            Annuler
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>