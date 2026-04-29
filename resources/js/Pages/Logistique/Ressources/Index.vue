<script setup>
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TabPanel from '@/Components/TabPanel.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    ressources: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            disponibles: 0,
            reservees: 0,
            utilisees: 0,
        }),
    },
    types: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'description', label: 'Description' },
    { key: 'quantite', label: 'Quantite' },
    { key: 'statut', label: 'Statut' },
    { key: 'actions', label: 'Actions' },
];

const tabs = props.types.map((type) => ({
    label: type.label,
    value: type.value,
}));

const activeTab = ref(props.filters.type || props.types[0]?.value || 'transport');
const statutFilter = ref(props.filters.statut || '');
const showCreateModal = ref(false);
const editingRessource = ref(null);

const form = useForm({
    type: activeTab.value,
    nom: '',
    description: '',
    quantite: 1,
    statut: props.statuts[0]?.value || 'disponible',
});

watch(activeTab, (value) => {
    form.type = value;
});

const filteredRows = computed(() => {
    const rows = props.ressources[activeTab.value] ?? [];

    if (!statutFilter.value) {
        return rows;
    }

    return rows.filter((item) => item.statut === statutFilter.value);
});

const openCreateModal = () => {
    editingRessource.value = null;
    form.reset();
    form.type = activeTab.value;
    form.quantite = 1;
    form.statut = props.statuts[0]?.value || 'disponible';
    showCreateModal.value = true;
};

const openEditModal = (ressource) => {
    editingRessource.value = ressource;
    form.type = ressource.type;
    form.nom = ressource.nom;
    form.description = ressource.description || '';
    form.quantite = ressource.quantite;
    form.statut = ressource.statut;
    showCreateModal.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            editingRessource.value = null;
            form.reset();
        },
    };

    if (editingRessource.value) {
        form.put(route('logistique.ressources.update', {
            evenement: props.evenement.id,
            ressource: editingRessource.value.id,
        }), options);

        return;
    }

    form.post(route('logistique.ressources.store', {
        evenement: props.evenement.id,
    }), options);
};

const destroyRessource = (ressourceId) => {
    router.delete(route('logistique.ressources.destroy', {
        evenement: props.evenement.id,
        ressource: ressourceId,
    }), {
        preserveScroll: true,
    });
};

const formatTypeCount = (type) => (props.ressources[type] ?? []).length;
</script>

<template>
    <Head :title="`Ressources - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Logistique · Ressources</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · suivi transport, matériel et restauration.</p>
                </div>

                <button
                    type="button"
                    class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                    @click="openCreateModal"
                >
                    Ajouter une ressource
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard label="Total ressources" :value="stats.total" color="blue" icon="M4 7h16M4 12h16M4 17h10" />
                <StatCard label="Disponibles" :value="stats.disponibles" color="green" icon="M5 13l4 4L19 7" />
                <StatCard label="Reservees" :value="stats.reservees" color="slate" icon="M12 8v4l3 3" />
                <StatCard label="Utilisees" :value="stats.utilisees" color="blue" icon="M3 12h18" />
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Catalogue par type</h2>
                        <p class="mt-1 text-sm text-slate-500">Chaque onglet regroupe les ressources opérationnelles de l'événement.</p>
                    </div>

                    <div class="w-full lg:w-64">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Filtrer par statut</label>
                        <select v-model="statutFilter" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les statuts</option>
                            <option v-for="statut in statuts" :key="statut.value" :value="statut.value">{{ statut.label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <TabPanel :tabs="tabs" :active-tab="activeTab" @update:active-tab="activeTab = $event">
                <template #default="{ activeTab: currentType }">
                    <section class="space-y-4">
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">
                                        {{ types.find((type) => type.value === currentType)?.label ?? currentType }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ formatTypeCount(currentType) }} ressource(s) enregistree(s).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <DataTable :columns="columns" :rows="filteredRows" empty-message="Aucune ressource pour ce type.">
                            <template #cell-nom="{ row }">
                                <span class="font-semibold text-slate-900">{{ row.nom }}</span>
                            </template>

                            <template #cell-description="{ row }">
                                <span class="text-sm text-slate-600">{{ row.description || 'Aucune description' }}</span>
                            </template>

                            <template #cell-quantite="{ row }">
                                <span class="font-medium text-slate-800">{{ row.quantite }}</span>
                            </template>

                            <template #cell-statut="{ row }">
                                <StatusBadge :status="row.statut" />
                            </template>

                            <template #cell-actions="{ row }">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                                        @click="openEditModal(row)"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100"
                                        @click="destroyRessource(row.id)"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </template>
                        </DataTable>
                    </section>
                </template>
            </TabPanel>
        </div>

        <Modal :show="showCreateModal" max-width="2xl" @close="showCreateModal = false">
            <div class="p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ editingRessource ? 'Modifier la ressource' : 'Ajouter une ressource' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Renseignez le type, la quantité et le statut opérationnel.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <select v-model="form.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Statut</label>
                        <select v-model="form.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="statut in statuts" :key="statut.value" :value="statut.value">{{ statut.label }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nom</label>
                        <input v-model="form.nom" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Quantite</label>
                        <input v-model="form.quantite" type="number" min="1" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showCreateModal = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="submit"
                    >
                        {{ editingRessource ? 'Enregistrer les changements' : 'Creer la ressource' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>