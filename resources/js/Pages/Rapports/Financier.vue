<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(BarElement, CategoryScale, Legend, LinearScale, Tooltip);

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    rapport: {
        type: Object,
        required: true,
    },
});

const barData = computed(() => ({
    labels: ['Recettes', 'Depenses'],
    datasets: [
        {
            label: 'Montants',
            data: [props.rapport.recettes_total, props.rapport.depenses],
            backgroundColor: ['#00A651', '#EF4444'],
            borderRadius: 12,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
};

const exportUrl = (format) => route('rapports.export', {
    evenement: props.evenement.id,
    type: 'financier',
    format,
});

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));
</script>

<template>
    <Head :title="`Rapport financier - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Rapport financier</h1>
                    <p class="text-sm text-slate-500">{{ evenement.titre }}</p>
                </div>
                <div class="flex gap-2">
                    <a :href="exportUrl('pdf')" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white hover:bg-[#005290]">Exporter PDF</a>
                    <a :href="exportUrl('excel')" class="rounded-2xl bg-[#00A651] px-4 py-3 text-sm font-semibold text-white hover:bg-[#008a45]">Exporter Excel</a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Budget previsionnel</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ formatCurrency(rapport.budget_previsionnel) }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Recettes</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ formatCurrency(rapport.recettes_total) }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Depenses</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ formatCurrency(rapport.depenses) }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Solde</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ formatCurrency(rapport.solde) }}</p></div>
            </div>

            <div v-if="rapport.depassement" class="rounded-3xl border border-red-200 bg-red-50 p-5 text-sm font-semibold text-red-700">
                Attention : le budget depasse le previsionnel de l'evenement.
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Recettes vs depenses</h2>
                    <div class="mt-6 h-72">
                        <Bar :data="barData" :options="chartOptions" />
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Lignes budgetaires</h2>
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Libelle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="ligne in rapport.budget.lignes" :key="ligne.id">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ ligne.libelle }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="ligne.type === 'recette' ? 'bg-[#00A651]/10 text-[#008a45]' : 'bg-red-100 text-red-700'">
                                            {{ ligne.type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ formatCurrency(ligne.montant) }}</td>
                                </tr>
                                <tr v-if="rapport.budget.lignes.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">Aucune ligne budgetaire disponible.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>