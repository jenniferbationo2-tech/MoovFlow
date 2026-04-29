<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Doughnut } from 'vue-chartjs';
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(ArcElement, Legend, Tooltip);

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

const pieData = computed(() => ({
    labels: ['Presents', 'Absents'],
    datasets: [
        {
            data: [props.rapport.presents, props.rapport.absents],
            backgroundColor: ['#00A651', '#EF4444'],
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const exportUrl = (format) => route('rapports.export', {
    evenement: props.evenement.id,
    type: 'participation',
    format,
});

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Non scanne';
</script>

<template>
    <Head :title="`Rapport participation - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Rapport participation</h1>
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
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Inscrits</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ rapport.inscrits }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Presents</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ rapport.presents }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Absents</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ rapport.absents }}</p></div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Taux presence</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ rapport.taux_presence }}%</p></div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.85fr,1.15fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Repartition presents / absents</h2>
                    <div class="mt-6 h-72">
                        <Doughnut :data="pieData" :options="chartOptions" />
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Tableau detaille participants</h2>
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Participant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Presence</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Scan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="participant in rapport.participants" :key="participant.id">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ participant.nom }}</p>
                                        <p class="text-xs text-slate-500">{{ participant.statut_inscription }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ participant.email }}<br>
                                        <span class="text-xs text-slate-500">{{ participant.telephone }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="participant.presence === 'present' ? 'bg-[#00A651]/10 text-[#008a45]' : 'bg-red-100 text-red-700'">
                                            {{ participant.presence === 'present' ? 'Present' : 'Absent' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ formatDateTime(participant.scan_time) }}</td>
                                </tr>
                                <tr v-if="rapport.participants.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Aucun participant pour ce rapport.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>