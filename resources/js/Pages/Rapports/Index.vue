<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    evenements: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        required: true,
    },
});

const form = reactive({
    type: props.filters.type ?? '',
    periode_debut: props.filters.periode_debut ?? '',
    periode_fin: props.filters.periode_fin ?? '',
});

const applyFilters = () => {
    router.get(route('rapports.index'), form, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value))
    : 'Date non definie';

const exportUrl = (eventId, type, format) => route('rapports.export', {
    evenement: eventId,
    type,
    format,
});
</script>

<template>
    <Head title="Rapports" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Rapports</h1>
                <p class="text-sm text-slate-500">Suivi, analyse et exports centralises par evenement.</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type evenement</label>
                        <select v-model="form.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="type in types" :key="type.id" :value="type.id">{{ type.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Periode debut</label>
                        <input v-model="form.periode_debut" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Periode fin</label>
                        <input v-model="form.periode_fin" type="date" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="w-full rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="applyFilters">
                            Filtrer les rapports
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Evenement</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Budget</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rapports</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Exports</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="evenement in evenements" :key="evenement.id" class="align-top">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ evenement.titre }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Statut : {{ evenement.statut }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ evenement.type }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ formatDate(evenement.date_debut) }}<br>
                                    <span class="text-xs text-slate-500">au {{ formatDate(evenement.date_fin) }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ formatCurrency(evenement.budget_previsionnel) }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <Link :href="route('rapports.participation', evenement.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Participation</Link>
                                        <Link :href="route('rapports.financier', evenement.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Financier</Link>
                                        <Link :href="route('rapports.rse', evenement.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">RSE</Link>
                                        <Link :href="route('rapports.satisfaction', evenement.id)" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Satisfaction</Link>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a :href="exportUrl(evenement.id, 'participation', 'pdf')" class="rounded-xl bg-[#0066B3] px-3 py-2 text-xs font-semibold text-white hover:bg-[#005290]">PDF</a>
                                        <a :href="exportUrl(evenement.id, 'financier', 'excel')" class="rounded-xl bg-[#00A651] px-3 py-2 text-xs font-semibold text-white hover:bg-[#008a45]">Excel</a>
                                        <a :href="exportUrl(evenement.id, 'presentation', 'ppt')" class="rounded-xl bg-[#8B5CF6] px-3 py-2 text-xs font-semibold text-white hover:bg-[#7c3aed]">PowerPoint</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="evenements.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Aucun rapport disponible pour ces filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>