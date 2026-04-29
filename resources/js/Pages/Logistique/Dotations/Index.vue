<script setup>
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TabPanel from '@/Components/TabPanel.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    dotations: {
        type: Array,
        default: () => [],
    },
    participants: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_distribue: 0,
            retourne: 0,
            en_cours: 0,
            manquant: 0,
        }),
    },
    alerts: {
        type: Array,
        default: () => [],
    },
    focusSection: {
        type: String,
        default: 'liste',
    },
});

const tabs = [
    { label: 'Dotations', value: 'liste' },
    { label: 'Tracking', value: 'tracking' },
];

const columns = [
    { key: 'equipement', label: 'Equipement' },
    { key: 'participant', label: 'Participant' },
    { key: 'remise', label: 'Date remise' },
    { key: 'etat_depart', label: 'Etat depart' },
    { key: 'retour', label: 'Date retour' },
    { key: 'etat_retour', label: 'Etat retour' },
    { key: 'statut', label: 'Statut' },
    { key: 'actions', label: 'Actions' },
];

const currentTab = ref(props.focusSection || 'liste');
const showStoreModal = ref(false);
const showReturnModal = ref(false);
const selectedDotation = ref(null);

const storeForm = useForm({
    user_id: '',
    equipement: '',
    date_remise: '',
    date_retour_prevue: '',
    etat_depart: '',
});

const returnForm = useForm({
    date_retour: '',
    etat_retour: '',
});

const openStoreModal = () => {
    storeForm.reset();
    showStoreModal.value = true;
};

const openReturnModal = (dotation) => {
    selectedDotation.value = dotation;
    returnForm.date_retour = dotation.date_retour || '';
    returnForm.etat_retour = dotation.etat_retour || '';
    showReturnModal.value = true;
};

const submitStore = () => {
    storeForm.post(route('logistique.dotations.store', {
        evenement: props.evenement.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showStoreModal.value = false;
            storeForm.reset();
        },
    });
};

const submitReturn = () => {
    if (!selectedDotation.value) {
        return;
    }

    returnForm.patch(route('logistique.dotations.return', {
        evenement: props.evenement.id,
        dotation: selectedDotation.value.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showReturnModal.value = false;
            selectedDotation.value = null;
            returnForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`Dotations - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Logistique · Dotations</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · distribution, retours et suivi des equipements.</p>
                </div>

                <div class="flex gap-3">
                    <Link
                        :href="route('logistique.dotations.tracking', { evenement: evenement.id })"
                        class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Vue tracking
                    </Link>
                    <button
                        type="button"
                        class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="openStoreModal"
                    >
                        Distribuer un equipement
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard label="Distribues" :value="stats.total_distribue" color="blue" icon="M4 7h16M4 12h16M4 17h10" />
                <StatCard label="Retournes" :value="stats.retourne" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="En cours" :value="stats.en_cours" color="slate" icon="M12 8v4l3 3" />
                <StatCard label="Manquants" :value="stats.manquant" color="blue" icon="M12 9v4m0 4h.01" />
            </div>

            <TabPanel :tabs="tabs" :active-tab="currentTab" @update:active-tab="currentTab = $event">
                <template #default="{ activeTab }">
                    <section v-show="activeTab === 'liste'" class="space-y-4">
                        <DataTable :columns="columns" :rows="dotations" empty-message="Aucune dotation enregistree.">
                            <template #cell-equipement="{ row }">
                                <span class="font-semibold text-slate-900">{{ row.equipement }}</span>
                            </template>

                            <template #cell-participant="{ row }">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ row.participant.name }}</p>
                                    <p class="text-xs text-slate-500">{{ row.participant.email }}</p>
                                </div>
                            </template>

                            <template #cell-remise="{ row }">
                                <div>
                                    <p class="text-sm text-slate-800">{{ row.date_remise || 'N/A' }}</p>
                                    <p class="text-xs text-slate-500">Retour prevu : {{ row.date_retour_prevue || 'Non defini' }}</p>
                                </div>
                            </template>

                            <template #cell-etat_depart="{ row }">
                                <span>{{ row.etat_depart || 'Non renseigne' }}</span>
                            </template>

                            <template #cell-retour="{ row }">
                                <span>{{ row.date_retour || 'En attente' }}</span>
                            </template>

                            <template #cell-etat_retour="{ row }">
                                <span>{{ row.etat_retour || 'Non retourne' }}</span>
                            </template>

                            <template #cell-statut="{ row }">
                                <StatusBadge :status="row.statut" />
                            </template>

                            <template #cell-actions="{ row }">
                                <button
                                    type="button"
                                    class="rounded-xl bg-[#0066B3]/10 px-3 py-2 text-xs font-semibold text-[#0066B3] hover:bg-[#0066B3]/20"
                                    :disabled="row.statut === 'retourne'"
                                    @click="openReturnModal(row)"
                                >
                                    Enregistrer retour
                                </button>
                            </template>
                        </DataTable>
                    </section>

                    <section v-show="activeTab === 'tracking'" class="space-y-4">
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-lg font-semibold text-slate-900">Suivi global des equipements</h2>
                            <p class="mt-1 text-sm text-slate-500">Alertes automatiques sur les materiels fortement en circulation.</p>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div
                                v-for="alert in alerts"
                                :key="alert.equipement"
                                class="rounded-3xl border border-amber-200 bg-amber-50 p-5"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800">{{ alert.equipement }}</p>
                                        <p class="mt-1 text-xs text-amber-700">Surveillance du stock en circulation</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-700">
                                        {{ alert.en_circulation }}/{{ alert.total }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="!alerts.length" class="rounded-3xl border border-dashed border-slate-300 p-6 text-sm text-slate-500">
                                Aucune alerte de stock bas pour le moment.
                            </div>
                        </div>
                    </section>
                </template>
            </TabPanel>
        </div>

        <Modal :show="showStoreModal" max-width="2xl" @close="showStoreModal = false">
            <div class="p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Distribuer un equipement</h3>
                    <p class="mt-1 text-sm text-slate-500">Renseignez le participant et l'etat initial du materiel.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Participant</label>
                        <select v-model="storeForm.user_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Selectionner un participant</option>
                            <option v-for="participant in participants" :key="participant.id" :value="participant.id">
                                {{ participant.name }} · {{ participant.email }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Equipement</label>
                        <input v-model="storeForm.equipement" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date de remise</label>
                        <input v-model="storeForm.date_remise" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date retour prevue</label>
                        <input v-model="storeForm.date_retour_prevue" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Etat de depart</label>
                        <input v-model="storeForm.etat_depart" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showStoreModal = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="submitStore"
                    >
                        Distribuer
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showReturnModal" max-width="xl" @close="showReturnModal = false">
            <div class="p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Enregistrer le retour</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ selectedDotation?.equipement || 'Equipement' }} · renseignez la date et l'etat au retour.</p>
                </div>

                <div class="mt-6 grid gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Date retour</label>
                        <input v-model="returnForm.date_retour" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Etat retour</label>
                        <input v-model="returnForm.etat_retour" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showReturnModal = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="submitReturn"
                    >
                        Enregistrer le retour
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>