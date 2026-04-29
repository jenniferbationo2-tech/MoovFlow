<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    meetings: {
        type: Array,
        default: () => [],
    },
    contacts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'prospect', label: 'Prospect' },
    { key: 'societe', label: 'Société' },
    { key: 'objet', label: 'Objet' },
    { key: 'date', label: 'Date' },
    { key: 'statut', label: 'Statut' },
    { key: 'score_lead', label: 'Score lead' },
];

const showForm = ref(false);

const form = useForm({
    prospect_id: '',
    prospect_nom: '',
    prospect_email: '',
    prospect_societe: '',
    objet: '',
    date_rdv: '',
    lieu: '',
    statut: 'planifie',
    notes: '',
});

const groupedAgenda = computed(() => {
    return props.meetings.reduce((carry, meeting) => {
        const dateKey = meeting.date
            ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(meeting.date))
            : 'Date non définie';

        if (!carry[dateKey]) {
            carry[dateKey] = [];
        }

        carry[dateKey].push(meeting);

        return carry;
    }, {});
});

const submit = () => {
    form.post(route('crm.b2b.store', props.evenement.id), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            form.statut = 'planifie';
        },
    });
};

const hydrateProspect = (event) => {
    const contact = props.contacts.find((item) => String(item.id) === String(event.target.value));

    form.prospect_nom = contact?.name ?? '';
    form.prospect_email = contact?.email ?? '';
    form.prospect_societe = '';
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';

const scoreClass = (score) => {
    if (score >= 70) {
        return 'bg-red-100 text-red-700';
    }

    if (score >= 40) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head :title="`B2B - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">RDV B2B · {{ evenement.titre }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Gestion des rendez-vous salon, prospection et suivi commercial.</p>
                </div>

                <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="showForm = !showForm">
                    Planifier RDV
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Total RDV" :value="stats.total ?? 0" color="blue" icon="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                <StatCard label="Confirmés" :value="stats.confirmes ?? 0" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="Réalisés" :value="stats.realises ?? 0" color="slate" icon="M12 8v8m-4-4h8" />
            </div>

            <div v-if="showForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Planifier un rendez-vous</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Prospect connu</label>
                        <select v-model="form.prospect_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" @change="hydrateProspect">
                            <option value="">Sélection libre</option>
                            <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                                {{ contact.name }} · {{ contact.score }}/100
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Objet</label>
                        <input v-model="form.objet" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nom prospect</label>
                        <input v-model="form.prospect_nom" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email prospect</label>
                        <input v-model="form.prospect_email" type="email" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Société</label>
                        <input v-model="form.prospect_societe" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date et heure</label>
                        <input v-model="form.date_rdv" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Lieu</label>
                        <input v-model="form.lieu" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="form.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="planifie">planifie</option>
                            <option value="confirme">confirme</option>
                            <option value="realise">realise</option>
                            <option value="annule">annule</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="submit">
                        Enregistrer
                    </button>
                </div>
            </div>

            <DataTable :columns="columns" :rows="meetings" empty-message="Aucun rendez-vous B2B planifié.">
                <template #cell-prospect="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.prospect }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.email || 'Email non renseigné' }}</p>
                    </div>
                </template>
                <template #cell-date="{ row }">
                    <span>{{ formatDate(row.date) }}</span>
                </template>
                <template #cell-statut="{ row }">
                    <StatusBadge :status="row.statut" />
                </template>
                <template #cell-score_lead="{ row }">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="scoreClass(row.score_lead ?? 0)">
                        {{ row.score_lead ?? 'N/A' }}
                    </span>
                </template>
            </DataTable>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Agenda visuel des RDV</h2>
                <div class="mt-5 space-y-4">
                    <div v-for="(items, day) in groupedAgenda" :key="day" class="rounded-2xl border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ day }}</h3>
                        <div class="mt-3 grid gap-3">
                            <div v-for="meeting in items" :key="meeting.id" class="rounded-2xl bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ meeting.prospect }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ meeting.objet }} · {{ meeting.lieu || 'Lieu à confirmer' }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-slate-700">{{ formatDate(meeting.date) }}</span>
                                        <StatusBadge :status="meeting.statut" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>