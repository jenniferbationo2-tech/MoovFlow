<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bar, Doughnut } from 'vue-chartjs';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';

ChartJS.register(ArcElement, BarElement, CategoryScale, Legend, LinearScale, Tooltip);

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    overview: {
        type: Object,
        required: true,
    },
    analyse: {
        type: Object,
        required: true,
    },
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};
</script>

<template>
    <Head :title="`Satisfaction - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Enquetes de satisfaction</h1>
                <p class="text-sm text-slate-500">{{ evenement.titre }}</p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-6 xl:grid-cols-[0.8fr,1.2fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Taux de satisfaction global</h2>
                    <div class="mt-6 flex items-center justify-center">
                        <div class="flex h-52 w-52 items-center justify-center rounded-full border-[18px] border-[#0066B3]/20">
                            <div class="flex h-36 w-36 items-center justify-center rounded-full bg-[#0066B3]/10 text-center">
                                <div>
                                    <p class="text-4xl font-bold text-[#0066B3]">{{ analyse.satisfaction_globale }}%</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-600">Satisfaction</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-900">Enquetes disponibles</h2>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ overview.enquetes.length }} enquete(s)</span>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <Link
                            v-for="enquete in overview.enquetes"
                            :key="enquete.id"
                            :href="route('rapports.satisfaction.analyse', enquete.id)"
                            class="rounded-2xl border border-slate-200 p-4 transition hover:border-[#0066B3] hover:bg-slate-50"
                        >
                            <p class="font-semibold text-slate-900">{{ enquete.titre }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ enquete.type }} · {{ enquete.statut }}</p>
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ enquete.reponses_count }} reponses</p>
                        </Link>
                        <div v-if="overview.enquetes.length === 0" class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500">
                            Aucune enquete disponible pour cet evenement.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                <div class="space-y-6">
                    <div
                        v-for="chart in analyse.question_charts"
                        :key="chart.question_id"
                        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-lg font-semibold text-slate-900">{{ chart.label }}</h2>
                            <span v-if="chart.average !== null" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Moyenne {{ chart.average }}/5</span>
                        </div>

                        <div v-if="chart.type === 'pie'" class="mt-6 h-72">
                            <Doughnut :data="{ labels: chart.labels, datasets: chart.datasets }" :options="chartOptions" />
                        </div>

                        <div v-else-if="chart.type === 'bar'" class="mt-6 h-72">
                            <Bar :data="{ labels: chart.labels, datasets: chart.datasets }" :options="chartOptions" />
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <div
                                v-for="(entry, index) in chart.entries"
                                :key="`${chart.question_id}-${index}`"
                                class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700"
                            >
                                {{ entry }}
                            </div>
                            <p v-if="chart.entries.length === 0" class="text-sm text-slate-500">Aucune reponse textuelle pour cette question.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Points forts</h2>
                        <ul class="mt-5 space-y-3">
                            <li v-for="(point, index) in analyse.points_forts" :key="`fort-${index}`" class="rounded-2xl bg-[#00A651]/10 px-4 py-3 text-sm text-[#166534]">{{ point }}</li>
                            <li v-if="analyse.points_forts.length === 0" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Aucun point fort detecte automatiquement.</li>
                        </ul>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Points a ameliorer</h2>
                        <ul class="mt-5 space-y-3">
                            <li v-for="(point, index) in analyse.points_amelioration" :key="`amelioration-${index}`" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-700">{{ point }}</li>
                            <li v-if="analyse.points_amelioration.length === 0" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Aucun point d'amelioration detecte automatiquement.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>