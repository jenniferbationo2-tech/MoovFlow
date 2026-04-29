<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    contact: {
        type: Object,
        required: true,
    },
    evenements: {
        type: Array,
        default: () => [],
    },
    followupTypes: {
        type: Array,
        default: () => [],
    },
    upcomingEvenements: {
        type: Array,
        default: () => [],
    },
});

const showFollowupForm = ref(false);
const showInvitationForm = ref(false);

const followupForm = useForm({
    user_id: props.contact.id,
    evenement_id: props.evenements[0]?.id ?? '',
    type: props.followupTypes[0] ?? 'remerciement',
    statut: 'a_faire',
    notes: '',
    date_prevue: '',
});

const invitationForm = useForm({
    user_id: props.contact.id,
    evenement_id: props.upcomingEvenements[0]?.id ?? '',
});

const timelineColumns = [
    { key: 'type', label: 'Type' },
    { key: 'titre', label: 'Interaction' },
    { key: 'date', label: 'Date' },
    { key: 'statut', label: 'Statut' },
];

const followupColumns = [
    { key: 'type', label: 'Type' },
    { key: 'statut', label: 'Statut' },
    { key: 'date_prevue', label: 'Date prévue' },
    { key: 'notes', label: 'Notes' },
];

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';

const leadBadgeClass = computed(() => ({
    chaud: 'bg-red-100 text-red-700',
    tiede: 'bg-amber-100 text-amber-700',
    froid: 'bg-slate-100 text-slate-700',
}[props.contact.classification] ?? 'bg-slate-100 text-slate-700'));

const submitFollowup = () => {
    followupForm.post(route('crm.followups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showFollowupForm.value = false;
            followupForm.reset('type', 'statut', 'notes', 'date_prevue');
            followupForm.user_id = props.contact.id;
        },
    });
};

const sendPriorityInvitation = () => {
    invitationForm.post(route('crm.loyalty.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            showInvitationForm.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`CRM - ${contact.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ contact.name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Profil CRM complet, historique des échanges et potentiel commercial.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="showFollowupForm = !showFollowupForm">
                        Créer suivi
                    </button>
                    <button type="button" class="rounded-2xl bg-[#00A651] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="showInvitationForm = !showInvitationForm">
                        Envoyer invitation
                    </button>
                    <Link :href="route('crm.contacts.index')" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                        Retour
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Email</p>
                        <p class="mt-2 text-sm text-slate-900">{{ contact.email }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Téléphone</p>
                        <p class="mt-2 text-sm text-slate-900">{{ contact.telephone || 'Non renseigné' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Société</p>
                        <p class="mt-2 text-sm text-slate-900">{{ contact.societe || 'Non renseignée' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Participation</p>
                        <p class="mt-2 text-sm text-slate-900">{{ contact.nb_evenements }} événements</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Lead scoring</h2>
                        <p class="mt-1 text-sm text-slate-500">Score calculé selon participation, ancienneté et interactions CRM.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-3xl font-bold text-slate-900">{{ contact.score }}/100</span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="leadBadgeClass">
                            {{ contact.classification }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="showFollowupForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Nouveau suivi</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Événement</label>
                        <select v-model="followupForm.evenement_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Aucun événement</option>
                            <option v-for="evenement in evenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <select v-model="followupForm.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="type in followupTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date prévue</label>
                        <input v-model="followupForm.date_prevue" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="followupForm.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="a_faire">a_faire</option>
                            <option value="fait">fait</option>
                            <option value="annule">annule</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Notes</label>
                        <textarea v-model="followupForm.notes" rows="3" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="submitFollowup">
                        Enregistrer
                    </button>
                </div>
            </div>

            <div v-if="showInvitationForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Invitation prioritaire</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Événement cible</label>
                        <select v-model="invitationForm.evenement_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Sélectionner</option>
                            <option v-for="evenement in upcomingEvenements" :key="evenement.id" :value="evenement.id">{{ evenement.titre }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="sendPriorityInvitation">
                        Envoyer
                    </button>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Timeline interactions</h2>
                <div class="mt-4">
                    <DataTable :columns="timelineColumns" :rows="contact.timeline" empty-message="Aucune interaction disponible.">
                        <template #cell-date="{ row }">
                            <span>{{ formatDate(row.date) }}</span>
                        </template>
                        <template #cell-statut="{ row }">
                            <StatusBadge :status="row.statut" />
                        </template>
                    </DataTable>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Actions de suivi existantes</h2>
                <div class="mt-4">
                    <DataTable :columns="followupColumns" :rows="contact.followups" empty-message="Aucun suivi enregistré.">
                        <template #cell-statut="{ row }">
                            <StatusBadge :status="row.statut" />
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>