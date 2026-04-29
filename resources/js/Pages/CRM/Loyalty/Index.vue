<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    participants: {
        type: Array,
        default: () => [],
    },
    evenements: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'nb_evenements', label: 'Nb événements' },
    { key: 'anciennete', label: 'Ancienneté' },
    { key: 'dernier_evenement', label: 'Dernier événement' },
    { key: 'actions', label: 'Actions' },
];

const showForm = ref(false);

const form = useForm({
    user_id: '',
    evenement_id: props.evenements[0]?.id ?? '',
});

const openInvitationForm = (participant) => {
    form.user_id = participant.id;
    showForm.value = true;
};

const submit = () => {
    form.post(route('crm.loyalty.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="CRM Loyalty" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Programme fidélité</h1>
                <p class="mt-1 text-sm text-slate-500">Participants récurrents, ancienneté et invitations prioritaires.</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <StatCard label="Total fidèles" :value="stats.total_fideles ?? 0" color="blue" icon="M12 3l2.5 5L20 9l-4 4 .9 6L12 16.8 7.1 19 8 13 4 9l5.5-1L12 3Z" />
                <StatCard label="Nouveaux ce mois" :value="stats.nouveaux_ce_mois ?? 0" color="green" icon="M12 5v14m-7-7h14" />
            </div>

            <div v-if="showForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Envoyer une invitation prioritaire</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Événement</label>
                        <select v-model="form.evenement_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Sélectionner</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="submit">
                        Envoyer invitation prioritaire
                    </button>
                </div>
            </div>

            <DataTable :columns="columns" :rows="participants" empty-message="Aucun participant fidèle détecté.">
                <template #cell-name="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.email }}</p>
                    </div>
                </template>
                <template #cell-actions="{ row }">
                    <button type="button" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200" @click="openInvitationForm(row)">
                        Envoyer invitation prioritaire
                    </button>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>