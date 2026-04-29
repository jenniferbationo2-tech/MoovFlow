<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    contacts: {
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
        default: () => ({}),
    },
});

const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'email', label: 'Email' },
    { key: 'societe', label: 'Société' },
    { key: 'nb_evenements', label: 'Nb événements' },
    { key: 'score', label: 'Score lead' },
    { key: 'derniere_interaction', label: 'Dernière interaction' },
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
        router.get(route('crm.contacts.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const syncMoov = () => {
    router.post(route('crm.sync'));
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Aucune';

const leadBadgeClass = (classification) => ({
    chaud: 'bg-red-100 text-red-700',
    tiede: 'bg-amber-100 text-amber-700',
    froid: 'bg-slate-100 text-slate-700',
}[classification] ?? 'bg-slate-100 text-slate-700');

const scoreLabel = (row) => `${row.score}/100 · ${row.classification}`;

const contactRows = computed(() => props.contacts.data ?? []);
</script>

<template>
    <Head title="CRM Contacts" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">CRM · Contacts</h1>
                    <p class="mt-1 text-sm text-slate-500">Suivi commercial des participants avec interactions multi-événements.</p>
                </div>

                <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="syncMoov">
                    Sync CRM Moov
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard label="Contacts CRM" :value="stats.total ?? 0" color="blue" icon="M16 14a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0" />
                <StatCard label="Chauds" :value="stats.chauds ?? 0" color="green" icon="M12 3l2.5 5L20 9l-4 4 .9 6L12 16.8 7.1 19 8 13 4 9l5.5-1L12 3Z" />
                <StatCard label="Tièdes" :value="stats.tiedes ?? 0" color="slate" icon="M12 8v4l3 3" />
                <StatCard label="Froids" :value="stats.froids ?? 0" color="slate" icon="M6 18 18 6M6 6l12 12" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                        <input v-model="localFilters.search" type="text" placeholder="Nom, email, téléphone" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Événement</label>
                        <select v-model="localFilters.evenement" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les événements</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut lead</label>
                        <select v-model="localFilters.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les statuts</option>
                            <option v-for="statut in statuts" :key="statut" :value="statut">{{ statut }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns" :rows="contactRows" empty-message="Aucun contact CRM disponible.">
                <template #cell-nom="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.telephone || 'Téléphone non renseigné' }}</p>
                    </div>
                </template>
                <template #cell-email="{ row }">
                    <span>{{ row.email }}</span>
                </template>
                <template #cell-societe="{ row }">
                    <span>{{ row.societe || 'Non renseignée' }}</span>
                </template>
                <template #cell-score="{ row }">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="leadBadgeClass(row.classification)">
                        {{ scoreLabel(row) }}
                    </span>
                </template>
                <template #cell-derniere_interaction="{ row }">
                    <span>{{ formatDate(row.derniere_interaction) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('crm.contacts.show', row.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                        Voir profil CRM
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>