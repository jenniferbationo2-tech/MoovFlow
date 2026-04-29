<script setup>
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import StatCard from '@/Components/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    benevoles: {
        type: Array,
        default: () => [],
    },
    utilisateurs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_benevoles: 0,
            postes_couverts: 0,
        }),
    },
});

const columns = [
    { key: 'benevole', label: 'Benevole' },
    { key: 'poste', label: 'Poste' },
    { key: 'creneaux', label: 'Creneaux' },
    { key: 'actions', label: 'Actions' },
];

const showModal = ref(false);
const editingAffectation = ref(null);

const form = useForm({
    user_id: '',
    poste: '',
    creneau_debut: '',
    creneau_fin: '',
});

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
    : 'N/A';

const openCreateModal = () => {
    editingAffectation.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (affectation) => {
    editingAffectation.value = affectation;
    form.user_id = affectation.user.id;
    form.poste = affectation.poste;
    form.creneau_debut = affectation.creneau_debut?.slice(0, 16) || '';
    form.creneau_fin = affectation.creneau_fin?.slice(0, 16) || '';
    showModal.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            editingAffectation.value = null;
            form.reset();
        },
    };

    if (editingAffectation.value) {
        form.put(route('logistique.benevoles.update', {
            evenement: props.evenement.id,
            benevoleAffectation: editingAffectation.value.id,
        }), options);

        return;
    }

    form.post(route('logistique.benevoles.store', {
        evenement: props.evenement.id,
    }), options);
};

const destroyAffectation = (affectationId) => {
    router.delete(route('logistique.benevoles.destroy', {
        evenement: props.evenement.id,
        benevoleAffectation: affectationId,
    }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Benevoles - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Logistique · Benevoles</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · affectations, postes et rotations des equipes terrain.</p>
                </div>

                <div class="flex gap-3">
                    <Link
                        :href="route('logistique.benevoles.planning', { evenement: evenement.id })"
                        class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Voir le planning
                    </Link>
                    <button
                        type="button"
                        class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="openCreateModal"
                    >
                        Affecter un benevole
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <StatCard label="Total benevoles" :value="stats.total_benevoles" color="blue" icon="M16 14a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0" />
                <StatCard label="Postes couverts" :value="stats.postes_couverts" color="green" icon="M5 13l4 4L19 7" />
            </div>

            <DataTable :columns="columns" :rows="benevoles" empty-message="Aucune affectation benevole.">
                <template #cell-benevole="{ row }">
                    <div>
                        <p class="font-semibold text-slate-900">{{ row.user.name }}</p>
                        <p class="text-xs text-slate-500">{{ row.user.email }}</p>
                    </div>
                </template>

                <template #cell-poste="{ row }">
                    <span class="rounded-full bg-[#00A651]/10 px-3 py-1 text-xs font-semibold text-[#008a45]">{{ row.poste }}</span>
                </template>

                <template #cell-creneaux="{ row }">
                    <div>
                        <p class="font-medium text-slate-800">{{ formatDateTime(row.creneau_debut) }}</p>
                        <p class="text-xs text-slate-500">{{ formatDateTime(row.creneau_fin) }}</p>
                    </div>
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
                            @click="destroyAffectation(row.id)"
                        >
                            Retirer
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ editingAffectation ? 'Modifier une affectation' : 'Affecter un benevole' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Definissez le poste et le creneau de rotation.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Benevole</label>
                        <select v-model="form.user_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Selectionner un utilisateur</option>
                            <option v-for="user in utilisateurs" :key="user.id" :value="user.id">{{ user.name }} · {{ user.email }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Poste</label>
                        <input v-model="form.poste" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Debut</label>
                        <input v-model="form.creneau_debut" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Fin</label>
                        <input v-model="form.creneau_fin" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showModal = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="submit"
                    >
                        {{ editingAffectation ? 'Enregistrer' : 'Affecter' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>