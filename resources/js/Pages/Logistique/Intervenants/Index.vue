<script setup>
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TabPanel from '@/Components/TabPanel.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    intervenants: {
        type: Array,
        default: () => [],
    },
    sessions: {
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
            total_intervenants: 0,
            sessions_couvertes: 0,
        }),
    },
    perdiems: {
        type: Array,
        default: () => [],
    },
    focusSection: {
        type: String,
        default: 'liste',
    },
});

const tabs = [
    { label: 'Intervenants', value: 'liste' },
    { label: 'Perdiems', value: 'perdiems' },
];

const currentTab = ref(props.focusSection || 'liste');
const showAssignModal = ref(false);

const assignForm = useForm({
    user_id: '',
    session_id: '',
    role: 'intervenant',
});

const intervenantsColumns = [
    { key: 'intervenant', label: 'Intervenant' },
    { key: 'roles', label: 'Roles' },
    { key: 'sessions', label: 'Sessions assignees' },
];

const perdiemsColumns = [
    { key: 'intervenant', label: 'Intervenant' },
    { key: 'sessions', label: 'Sessions' },
    { key: 'perdiem', label: 'Perdiem estime' },
    { key: 'transport', label: 'Transport estime' },
    { key: 'total', label: 'Total estime' },
];

const submitAssignment = () => {
    assignForm.post(route('logistique.intervenants.assign', {
        evenement: props.evenement.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
            assignForm.role = 'intervenant';
        },
    });
};

const removeAssignment = (assignmentId) => {
    router.delete(route('logistique.intervenants.remove', {
        evenement: props.evenement.id,
        intervenantSession: assignmentId,
    }), {
        preserveScroll: true,
    });
};

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
    : 'N/A';

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value || 0));
</script>

<template>
    <Head :title="`Intervenants - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Logistique · Intervenants</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · coordination des intervenants et des prises en charge.</p>
                </div>

                <div class="flex gap-3">
                    <Link
                        :href="route('logistique.intervenants.perdiems', { evenement: evenement.id })"
                        class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Vue perdiems
                    </Link>
                    <button
                        type="button"
                        class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="showAssignModal = true"
                    >
                        Assigner a une session
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <StatCard label="Total intervenants" :value="stats.total_intervenants" color="blue" icon="M16 14a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0" />
                <StatCard label="Sessions couvertes" :value="stats.sessions_couvertes" color="green" icon="M5 13l4 4L19 7" />
            </div>

            <TabPanel :tabs="tabs" :active-tab="currentTab" @update:active-tab="currentTab = $event">
                <template #default="{ activeTab }">
                    <section v-show="activeTab === 'liste'" class="space-y-4">
                        <DataTable :columns="intervenantsColumns" :rows="intervenants" empty-message="Aucun intervenant assigne.">
                            <template #cell-intervenant="{ row }">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                    <p class="text-xs text-slate-500">{{ row.email }}</p>
                                </div>
                            </template>

                            <template #cell-roles="{ row }">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="role in row.roles"
                                        :key="role"
                                        class="rounded-full bg-[#0066B3]/10 px-3 py-1 text-xs font-semibold text-[#0066B3]"
                                    >
                                        {{ role }}
                                    </span>
                                </div>
                            </template>

                            <template #cell-sessions="{ row }">
                                <div class="space-y-3">
                                    <div
                                        v-for="session in row.sessions"
                                        :key="session.assignment_id"
                                        class="rounded-2xl border border-slate-200 p-3"
                                    >
                                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ session.titre }}</p>
                                                <p class="text-xs text-slate-500">
                                                    {{ formatDateTime(session.heure_debut) }} · {{ session.salle?.nom || 'Salle non definie' }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <StatusBadge status="en_cours" />
                                                <button
                                                    type="button"
                                                    class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100"
                                                    @click="removeAssignment(session.assignment_id)"
                                                >
                                                    Retirer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </section>

                    <section v-show="activeTab === 'perdiems'" class="space-y-4">
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-lg font-semibold text-slate-900">Synthese perdiems et notes de frais</h2>
                            <p class="mt-1 text-sm text-slate-500">Estimation automatique basee sur le nombre de sessions couvertes.</p>
                        </div>

                        <DataTable :columns="perdiemsColumns" :rows="perdiems" empty-message="Aucune donnee de perdiem disponible.">
                            <template #cell-intervenant="{ row }">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ row.intervenant }}</p>
                                    <p class="text-xs text-slate-500">{{ row.email }}</p>
                                </div>
                            </template>

                            <template #cell-sessions="{ row }">
                                <span class="font-medium text-slate-800">{{ row.sessions }}</span>
                            </template>

                            <template #cell-perdiem="{ row }">
                                <span>{{ formatCurrency(row.perdiem_estime) }}</span>
                            </template>

                            <template #cell-transport="{ row }">
                                <span>{{ formatCurrency(row.transport_estime) }}</span>
                            </template>

                            <template #cell-total="{ row }">
                                <span class="font-semibold text-slate-900">{{ formatCurrency(row.total_estime) }}</span>
                            </template>
                        </DataTable>
                    </section>
                </template>
            </TabPanel>
        </div>

        <Modal :show="showAssignModal" max-width="2xl" @close="showAssignModal = false">
            <div class="p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Assigner un intervenant</h3>
                    <p class="mt-1 text-sm text-slate-500">Selectionnez l'utilisateur, la session et le role d'intervention.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Intervenant</label>
                        <select v-model="assignForm.user_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Selectionner un intervenant</option>
                            <option v-for="user in utilisateurs" :key="user.id" :value="user.id">{{ user.name }} · {{ user.email }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Session</label>
                        <select v-model="assignForm.session_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Selectionner une session</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.titre }} · {{ formatDateTime(session.heure_debut) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Role</label>
                        <input v-model="assignForm.role" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showAssignModal = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        @click="submitAssignment"
                    >
                        Assigner
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>