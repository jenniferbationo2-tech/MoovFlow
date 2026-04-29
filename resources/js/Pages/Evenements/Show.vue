<script setup>
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TabPanel from '@/Components/TabPanel.vue';
import TypeBadge from '@/Components/TypeBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    budget: {
        type: Object,
        default: null,
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    tacheStatuts: {
        type: Array,
        default: () => [],
    },
    utilisateurs: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: 'resume',
    },
});

const tabs = [
    { label: 'Resume', value: 'resume' },
    { label: 'Sessions', value: 'sessions' },
    { label: 'Taches', value: 'taches' },
    { label: 'Budget', value: 'budget' },
    { label: 'Objectifs RSE', value: 'rse' },
    { label: 'Participants', value: 'participants' },
];

const currentTab = ref(props.activeTab);
const showDeleteModal = ref(false);
const statusForm = useForm({
    statut: props.evenement.statut,
});
const taskForm = useForm({
    titre: '',
    description: '',
    responsable_id: '',
    echeance: '',
    statut: 'a_faire',
});
const budgetForm = useForm({
    libelle: '',
    montant: 0,
    type: 'depense',
});

const sortedTasks = computed(() => [...props.evenement.taches].sort((left, right) => {
    return (left.echeance ?? '').localeCompare(right.echeance ?? '');
}));

const budgetLines = computed(() => props.budget?.lignes_budget ?? []);

const budgetSummary = computed(() => {
    const recettes = budgetLines.value
        .filter((line) => line.type === 'recette')
        .reduce((total, line) => total + Number(line.montant), 0);
    const depenses = budgetLines.value
        .filter((line) => line.type === 'depense')
        .reduce((total, line) => total + Number(line.montant), 0);

    return {
        recettes,
        depenses,
        solde: recettes - depenses,
    };
});

const nextStatuses = computed(() => {
    const flow = {
        brouillon: ['publie', 'annule'],
        publie: ['en_cours', 'annule'],
        en_cours: ['termine', 'annule'],
        termine: [],
        annule: [],
    };

    return props.statuts.filter((item) => flow[props.evenement.statut]?.includes(item.value));
});

const updateTab = (tab) => {
    currentTab.value = tab;
    router.get(route('evenements.show', props.evenement.id), { tab }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const updateStatus = () => {
    statusForm.patch(route('evenements.updateStatut', props.evenement.id), {
        preserveScroll: true,
    });
};

const addTask = () => {
    taskForm.post(route('evenements.taches.store', props.evenement.id), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const updateTaskStatus = (task, status) => {
    router.patch(route('taches.updateStatut', task.id), { statut: status }, {
        preserveScroll: true,
    });
};

const addBudgetLine = () => {
    if (!props.budget?.id) {
        return;
    }

    budgetForm.post(route('budgets.lignes.store', props.budget.id), {
        preserveScroll: true,
        onSuccess: () => budgetForm.reset('libelle', 'montant', 'type'),
    });
};

const deleteEvent = () => {
    router.delete(route('evenements.destroy', props.evenement.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: props.budget?.devise ?? 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Non defini';
</script>

<template>
    <Head :title="evenement.titre" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">Details evenement</h1>
                <p class="mt-1 text-sm text-slate-500">Une fiche complete pour piloter votre experience evenementielle.</p>
            </div>
        </template>

        <div class="space-y-6">
            <section class="overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-200">
                <div class="grid lg:grid-cols-[1.5fr,1fr]">
                    <div class="min-h-[320px] bg-gradient-to-br from-[#0066B3] to-[#004A82]">
                        <img
                            v-if="evenement.visuel_url"
                            :src="evenement.visuel_url"
                            :alt="evenement.titre"
                            class="h-full w-full object-cover"
                        >
                        <div v-else class="flex h-full items-end p-8 text-white">
                            <div>
                                <p class="text-xs uppercase tracking-[0.28em] text-white/70">MoovFlow</p>
                                <h2 class="mt-3 font-['Outfit'] text-4xl font-bold">{{ evenement.titre }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 p-6 lg:p-8">
                        <div class="flex flex-wrap items-center gap-3">
                            <TypeBadge :type="evenement.type_evenement" />
                            <StatusBadge :status="evenement.statut" />
                        </div>

                        <div>
                            <h2 class="font-['Outfit'] text-3xl font-bold text-slate-900">{{ evenement.titre }}</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ evenement.description || 'Aucune description renseignee.' }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Debut</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ formatDateTime(evenement.date_debut) }}</p>
                            </div>
                            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Fin</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ formatDateTime(evenement.date_fin) }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link :href="route('evenements.edit', evenement.id)" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]">
                                Modifier
                            </Link>
                            <button type="button" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="showDeleteModal = true">
                                ...
                            </button>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 p-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Mettre a jour le statut</label>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <select v-model="statusForm.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                    <option v-for="status in nextStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                                <button type="button" class="rounded-2xl bg-[#00A651] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" :disabled="!nextStatuses.length" @click="updateStatus">
                                    Confirmer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Inscriptions</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ evenement.inscriptions_count }}</p>
                </div>
                <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Sessions</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ evenement.sessions.length }}</p>
                </div>
                <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Taches</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ evenement.taches.length }}</p>
                </div>
                <div class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Budget prevu</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ formatCurrency(evenement.budget_prev) }}</p>
                </div>
            </section>

            <TabPanel :tabs="tabs" :active-tab="currentTab" @update:active-tab="updateTab">
                <template #default="{ activeTab }">
                    <section v-show="activeTab === 'resume'" class="grid gap-6 xl:grid-cols-[1.4fr,1fr]">
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Vue generale</h3>
                            <div class="mt-6 grid gap-4 md:grid-cols-2">
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Lieu</p>
                                    <p class="mt-2 font-semibold text-slate-900">{{ evenement.lieu?.nom ?? 'Non defini' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ evenement.lieu?.adresse ?? 'Adresse non renseignee' }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Organisateur</p>
                                    <p class="mt-2 font-semibold text-slate-900">{{ evenement.organisateur?.name ?? 'Non renseigne' }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Periode</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatDateTime(evenement.date_debut) }}</p>
                                    <p class="text-sm font-semibold text-slate-900">{{ formatDateTime(evenement.date_fin) }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">QR code</p>
                                    <img v-if="evenement.qr_code_url" :src="evenement.qr_code_url" alt="QR code" class="mt-3 h-28 w-28 rounded-2xl border border-slate-200 bg-white p-2">
                                    <p v-else class="mt-2 text-sm text-slate-500">Non disponible</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Synthese budgetaire</h3>
                            <div class="mt-6 space-y-4">
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Recettes</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ formatCurrency(budgetSummary.recettes) }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Depenses</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ formatCurrency(budgetSummary.depenses) }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Solde</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ formatCurrency(budgetSummary.solde) }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-show="activeTab === 'sessions'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="session in evenement.sessions" :key="session.id" class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Session</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ session.titre }}</h3>
                            <p class="mt-3 text-sm text-slate-600">{{ session.description || 'Aucune description renseignee.' }}</p>
                            <div class="mt-4 space-y-2 text-sm text-slate-500">
                                <p>{{ formatDateTime(session.heure_debut) }}</p>
                                <p>{{ formatDateTime(session.heure_fin) }}</p>
                                <p>{{ session.salle?.nom ?? 'Salle non definie' }}</p>
                            </div>
                        </article>
                        <div v-if="!evenement.sessions.length" class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
                            Aucune session n'est disponible pour le moment.
                        </div>
                    </section>

                    <section v-show="activeTab === 'taches'" class="grid gap-6 xl:grid-cols-[1.4fr,1fr]">
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Suivi des taches</h3>
                            <div class="mt-6 space-y-4">
                                <article v-for="task in sortedTasks" :key="task.id" class="rounded-[1.5rem] border border-slate-200 p-4">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h4 class="font-semibold text-slate-900">{{ task.titre }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ task.description || 'Aucune description.' }}</p>
                                            <p class="mt-2 text-xs text-slate-500">Responsable : {{ task.responsable?.name ?? 'Non affecte' }}</p>
                                            <p class="text-xs text-slate-500">Echeance : {{ task.echeance ?? 'Sans echeance' }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <StatusBadge :status="task.statut" />
                                            <button
                                                v-for="statut in tacheStatuts"
                                                :key="`${task.id}-${statut.value}`"
                                                type="button"
                                                class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                                                @click="updateTaskStatus(task, statut.value)"
                                            >
                                                {{ statut.label }}
                                            </button>
                                        </div>
                                    </div>
                                </article>
                                <div v-if="!sortedTasks.length" class="rounded-[1.5rem] border border-dashed border-slate-300 p-6 text-sm text-slate-500">
                                    Aucune tache n'est encore creee.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Ajouter une tache</h3>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                                    <input v-model="taskForm.titre" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                                    <textarea v-model="taskForm.description" rows="4" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Responsable</label>
                                    <select v-model="taskForm.responsable_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                        <option value="">Selectionner</option>
                                        <option v-for="utilisateur in utilisateurs" :key="utilisateur.id" :value="utilisateur.id">{{ utilisateur.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Echeance</label>
                                    <input v-model="taskForm.echeance" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                </div>
                                <button type="button" class="w-full rounded-2xl bg-[#00A651] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="addTask">
                                    Ajouter
                                </button>
                            </div>
                        </div>
                    </section>

                    <section v-show="activeTab === 'budget'" class="grid gap-6 xl:grid-cols-[1.35fr,1fr]">
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Budget detaille</h3>
                            <div class="mt-6 space-y-4">
                                <article v-for="line in budgetLines" :key="line.id" class="flex items-center justify-between rounded-[1.5rem] border border-slate-200 p-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ line.libelle }}</p>
                                        <p class="text-sm text-slate-500">{{ line.type === 'recette' ? 'Recette' : 'Depense' }}</p>
                                    </div>
                                    <p class="text-lg font-bold text-slate-900">{{ formatCurrency(line.montant) }}</p>
                                </article>
                                <div v-if="!budgetLines.length" class="rounded-[1.5rem] border border-dashed border-slate-300 p-6 text-sm text-slate-500">
                                    Aucune ligne budgetaire n'est disponible.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Ajouter une ligne</h3>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Libelle</label>
                                    <input v-model="budgetForm.libelle" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                                    <select v-model="budgetForm.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                        <option value="depense">Depense</option>
                                        <option value="recette">Recette</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Montant</label>
                                    <input v-model="budgetForm.montant" type="number" min="0" step="0.01" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                </div>
                                <button type="button" class="w-full rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="addBudgetLine">
                                    Ajouter la ligne
                                </button>
                            </div>
                        </div>
                    </section>

                    <section v-show="activeTab === 'rse'" class="grid gap-4 md:grid-cols-2">
                        <article v-for="objectif in evenement.objectifs_rse" :key="objectif.id" class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-semibold text-slate-900">{{ objectif.type_impact }}</h3>
                                <span class="rounded-full bg-[#00A651]/10 px-3 py-1 text-xs font-semibold text-[#008a45]">{{ objectif.score_environnemental ?? 0 }}%</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Beneficiaires directs</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ objectif.nb_beneficiaires_directs }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Beneficiaires indirects</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ objectif.nb_beneficiaires_indirects }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Associations</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ objectif.nb_associations_soutenues }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Emplois crees</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ objectif.nb_emplois_crees }}</p>
                                </div>
                            </div>
                        </article>
                        <div v-if="!evenement.objectifs_rse.length" class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
                            Aucun objectif RSE n'est encore renseigne.
                        </div>
                    </section>

                    <section v-show="activeTab === 'participants'" class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm text-slate-500">Inscriptions</p>
                            <p class="mt-3 text-3xl font-bold text-slate-900">{{ evenement.inscriptions_count }}</p>
                        </div>
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm text-slate-500">Taux de presence estime</p>
                            <p class="mt-3 text-3xl font-bold text-slate-900">{{ Math.round(evenement.inscriptions_count * 0.82) }}</p>
                        </div>
                        <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm text-slate-500">Objectifs RSE</p>
                            <p class="mt-3 text-3xl font-bold text-slate-900">{{ evenement.objectifs_rse.length }}</p>
                        </div>
                    </section>
                </template>
            </TabPanel>
        </div>

        <Modal
            :show="showDeleteModal"
            title="Supprimer cet evenement"
            description="L'evenement sera archive et retire des vues actives."
            confirm-text="Supprimer"
            cancel-text="Annuler"
            :danger="true"
            @close="showDeleteModal = false"
            @confirm="deleteEvent"
        />
    </AppLayout>
</template>