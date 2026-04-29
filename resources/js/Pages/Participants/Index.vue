<script setup>
import DataTable from '@/Components/DataTable.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    participants: {
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
});

const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'email', label: 'Email' },
    { key: 'telephone', label: 'Telephone' },
    { key: 'nb_evenements', label: 'Nb evenements' },
    { key: 'derniere_inscription', label: 'Derniere inscription' },
    { key: 'actions', label: 'Actions' },
];

const localFilters = reactive({
    search: props.filters.search ?? '',
    evenement: props.filters.evenement ?? '',
});

const importForm = useForm({
    file: null,
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('participants.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const setFile = (event) => {
    importForm.file = event.target.files[0];
};

const importParticipants = () => {
    importForm.post(route('participants.import'));
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Aucune';
</script>

<template>
    <Head title="Participants" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Participants</h1>
                    <p class="mt-1 text-sm text-slate-500">Annuaire et historique des participants.</p>
                </div>

                <form class="flex items-center gap-3" @submit.prevent="importParticipants">
                    <input type="file" accept=".csv,.xlsx,.xls" class="block text-sm text-slate-600" @change="setFile" />
                    <button type="submit" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white hover:bg-[#005290]" :disabled="importForm.processing || !importForm.file">
                        Import CSV
                    </button>
                </form>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Recherche</label>
                        <input v-model="localFilters.search" type="text" placeholder="Nom, email ou telephone" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Evenement</label>
                        <select v-model="localFilters.evenement" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les evenements</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns" :rows="participants.data" :pagination="participants" empty-message="Aucun participant trouve.">
                <template #cell-nom="{ row }">
                    <span class="font-semibold text-slate-900">{{ row.name }}</span>
                </template>
                <template #cell-email="{ row }">
                    <span>{{ row.email }}</span>
                </template>
                <template #cell-telephone="{ row }">
                    <span>{{ row.telephone || 'Non renseigne' }}</span>
                </template>
                <template #cell-derniere_inscription="{ row }">
                    <span>{{ formatDate(row.derniere_inscription) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('participants.show', row.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                        Voir profil
                    </Link>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>