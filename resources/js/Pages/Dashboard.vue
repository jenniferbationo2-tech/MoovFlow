<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

ChartJS.register(ArcElement, BarElement, CategoryScale, Filler, Legend, LineElement, LinearScale, PointElement, Tooltip);

const props = defineProps({
    cards: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    latest_events: {
        type: Array,
        default: () => [],
    },
    recent_activity: {
        type: Array,
        default: () => [],
    },
});

const cardItems = computed(() => [
    {
        label: 'Evenements actifs',
        value: props.cards.evenements_actifs,
        accent: 'border-[#0066B3]',
    },
    {
        label: 'Total inscrits',
        value: props.cards.total_inscrits,
        accent: 'border-[#00A651]',
    },
    {
        label: 'Taux presence',
        value: `${props.cards.taux_presence}%`,
        accent: 'border-[#F59E0B]',
    },
    {
        label: 'Budget total',
        value: formatCurrency(props.cards.budget_total.previsionnel),
        accent: 'border-[#8B5CF6]',
    },
    {
        label: 'Score RSE moyen',
        value: `${props.cards.score_rse_moyen}%`,
        accent: 'border-[#14B8A6]',
    },
    {
        label: 'Enquetes en cours',
        value: props.cards.enquetes_en_cours,
        accent: 'border-[#EF4444]',
    },
]);

const lineData = computed(() => ({
    labels: props.stats.monthly_trends.map((item) => item.month),
    datasets: [
        {
            label: 'Inscriptions',
            data: props.stats.monthly_trends.map((item) => item.value),
            borderColor: '#0066B3',
            backgroundColor: 'rgba(0, 102, 179, 0.15)',
            fill: true,
            tension: 0.35,
        },
    ],
}));

const pieData = computed(() => ({
    labels: props.stats.events_by_type.map((item) => item.label),
    datasets: [
        {
            data: props.stats.events_by_type.map((item) => item.value),
            backgroundColor: ['#0066B3', '#00A651', '#F59E0B', '#8B5CF6', '#EF4444', '#14B8A6'],
        },
    ],
}));

const barData = computed(() => ({
    labels: props.stats.events_by_status.map((item) => item.label),
    datasets: [
        {
            label: 'Evenements',
            data: props.stats.events_by_status.map((item) => item.value),
            backgroundColor: ['#0066B3', '#00A651', '#F59E0B', '#8B5CF6', '#EF4444'],
            borderRadius: 10,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
        },
    },
};

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value))
    : 'Date non definie';
</script>

<template>
    <Head title="Tableau de bord" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Tableau de bord</h2>
                    <p class="text-sm text-slate-500">Pilotage global des evenements, finances, RSE et satisfaction.</p>
                </div>
                <Link
                    :href="route('rapports.index')"
                    class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                >
                    Ouvrir les rapports
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="item in cardItems"
                    :key="item.label"
                    class="rounded-3xl border-l-4 bg-white p-6 shadow-sm ring-1 ring-slate-200"
                    :class="item.accent"
                >
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ item.label }}</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ item.value }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.4fr,0.9fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Evolution des inscriptions</h3>
                            <p class="text-sm text-slate-500">Suivi mensuel sur les 12 derniers mois.</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participants presents</p>
                            <p class="mt-1 text-xl font-bold text-slate-900">{{ stats.participants_present }}</p>
                        </div>
                    </div>
                    <div class="mt-6 h-80">
                        <Line :data="lineData" :options="chartOptions" />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Repartition par type</h3>
                        <div class="mt-5 h-64">
                            <Doughnut :data="pieData" :options="chartOptions" />
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Synthese budgetaire</h3>
                        <dl class="mt-5 space-y-4">
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="text-sm text-slate-500">Previsionnel</dt>
                                <dd class="text-lg font-bold text-slate-900">{{ formatCurrency(stats.budget_total.previsionnel) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="text-sm text-slate-500">Depense</dt>
                                <dd class="text-lg font-bold text-slate-900">{{ formatCurrency(stats.budget_total.depense) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="text-sm text-slate-500">Taux presence global</dt>
                                <dd class="text-lg font-bold text-slate-900">{{ stats.taux_presence }}%</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Evenements par statut</h3>
                    <div class="mt-6 h-80">
                        <Bar :data="barData" :options="chartOptions" />
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Activite recente</h3>
                    <div class="mt-5 space-y-3">
                        <div
                            v-for="(activity, index) in recent_activity"
                            :key="`${activity.type}-${index}`"
                            class="rounded-2xl border border-slate-200 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ activity.label }}</p>
                                <span class="text-xs text-slate-500">{{ formatDate(activity.date) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ activity.description }}</p>
                        </div>
                        <p v-if="recent_activity.length === 0" class="text-sm text-slate-500">Aucune activite recente disponible.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Derniers evenements</h3>
                        <p class="text-sm text-slate-500">Les 5 derniers evenements suivis par le systeme.</p>
                    </div>
                    <Link :href="route('evenements.index')" class="text-sm font-semibold text-[#0066B3]">
                        Voir tous les evenements
                    </Link>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Evenement</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date debut</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Inscrits</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="event in latest_events" :key="event.id">
                                <td class="px-4 py-3">
                                    <Link :href="route('evenements.show', event.id)" class="font-semibold text-slate-900 hover:text-[#0066B3]">
                                        {{ event.titre }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ event.type }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ event.statut }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ formatDate(event.date_debut) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ event.inscriptions_count }}</td>
                            </tr>
                            <tr v-if="latest_events.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Aucun evenement recent disponible.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>