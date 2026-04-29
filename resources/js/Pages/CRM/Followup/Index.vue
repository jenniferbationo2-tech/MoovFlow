<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    followups: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    types: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    contacts: {
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
    { key: 'contact', label: 'Contact' },
    { key: 'type', label: 'Type' },
    { key: 'statut', label: 'Statut' },
    { key: 'date_prevue', label: 'Date prévue' },
    { key: 'notes', label: 'Notes' },
];

const showForm = ref(false);

const localFilters = reactive({
    type: props.filters.type ?? '',
    statut: props.filters.statut ?? '',
});

const form = useForm({
    user_id: props.contacts[0]?.id ?? '',
    evenement_id: '',
    type: props.types[0] ?? 'remerciement',
    statut: 'a_faire',
    notes: '',
    date_prevue: '',
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('crm.followups.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const submit = () => {
    form.post(route('crm.followups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset('evenement_id', 'type', 'statut', 'notes', 'date_prevue');
            form.user_id = props.contacts[0]?.id ?? '';
        },
    });
};
</script>

<template>
    <Head title="CRM Follow-up" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Suivi post-événement</h1>
                    <p class="mt-1 text-sm text-slate-500">Pilotage des relances, remerciements et actions de fidélisation.</p>
                </div>

                <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="showForm = !showForm">
                    Créer suivi
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="À faire" :value="stats.a_faire ?? 0" color="blue" icon="M12 8v4l3 3" />
                <StatCard label="Faits" :value="stats.fait ?? 0" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="En retard" :value="stats.en_retard ?? 0" color="slate" icon="M12 9v4m0 4h.01" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <select v-model="localFilters.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="localFilters.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="statut in statuts" :key="statut" :value="statut">{{ statut }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Nouvelle action</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Contact</label>
                        <select v-model="form.user_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="contact in contacts" :key="contact.id" :value="contact.id">{{ contact.name }} · {{ contact.email }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Événement</label>
                        <select v-model="form.evenement_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Aucun</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <select v-model="form.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date prévue</label>
                        <input v-model="form.date_prevue" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="4" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="submit">
                        Enregistrer
                    </button>
                </div>
            </div>

            <DataTable :columns="columns" :rows="followups" empty-message="Aucune action de suivi disponible.">
                <template #cell-contact="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.contact.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.contact.email }}</p>
                    </div>
                </template>
                <template #cell-type="{ row }">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ row.type }}</span>
                </template>
                <template #cell-statut="{ row }">
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="row.statut" />
                        <span v-if="row.en_retard" class="text-xs font-semibold text-red-600">En retard</span>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>