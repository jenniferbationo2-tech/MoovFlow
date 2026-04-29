<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Bar, Pie } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';

ChartJS.register(ArcElement, BarElement, CategoryScale, Legend, LinearScale, Title, Tooltip);

const props = defineProps({
    enquete: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            nb_reponses: 0,
            participants_count: 0,
            taux_reponse: 0,
        }),
    },
    chartData: {
        type: Array,
        default: () => [],
    },
    responses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    reponses: Object.fromEntries((props.enquete.questions ?? []).map((question) => [question.id, ''])),
});

const submitResponse = () => {
    form.post(route('communication.enquetes.respond', {
        evenement: props.enquete.evenement.id,
        enquete: props.enquete.id,
    }));
};

const publish = () => {
    router.patch(route('communication.enquetes.publish', {
        evenement: props.enquete.evenement.id,
        enquete: props.enquete.id,
    }));
};

const closeSurvey = () => {
    router.patch(route('communication.enquetes.close', {
        evenement: props.enquete.evenement.id,
        enquete: props.enquete.id,
    }));
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Date indisponible';
</script>

<template>
    <Head :title="`Résultats enquête - ${enquete.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ enquete.titre }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ enquete.evenement.titre }} · pilotage des résultats et réponses individuelles.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="publish">
                        Publier
                    </button>
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="closeSurvey">
                        Clôturer
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-500">Nombre de réponses</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.nb_reponses }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-500">Participants ciblés</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.participants_count }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-500">Taux de réponse</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.taux_reponse }}%</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <div class="space-y-6">
                    <div
                        v-for="chart in chartData"
                        :key="chart.question_id"
                        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                    >
                        <h2 class="text-lg font-semibold text-slate-900">{{ chart.label }}</h2>

                        <div v-if="chart.type === 'pie'" class="mt-5 h-80">
                            <Pie :data="{ labels: chart.labels, datasets: chart.datasets }" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>

                        <div v-else-if="chart.type === 'bar'" class="mt-5 h-80">
                            <Bar :data="{ labels: chart.labels, datasets: chart.datasets }" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>

                        <div v-else class="mt-5 space-y-2">
                            <div
                                v-for="(entry, index) in chart.entries"
                                :key="`${chart.question_id}-${index}`"
                                class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700"
                            >
                                {{ entry }}
                            </div>
                            <p v-if="chart.entries.length === 0" class="text-sm text-slate-500">Aucune réponse textuelle pour cette question.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Répondre à l’enquête</h2>
                        <div class="mt-5 space-y-4">
                            <div v-for="question in enquete.questions" :key="question.id">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ question.label }}</label>

                                <textarea
                                    v-if="question.type === 'texte'"
                                    v-model="form.reponses[question.id]"
                                    rows="3"
                                    class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]"
                                />

                                <select
                                    v-else-if="question.type === 'choix_multiple'"
                                    v-model="form.reponses[question.id]"
                                    class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]"
                                >
                                    <option value="">Sélectionner</option>
                                    <option v-for="option in question.options" :key="option" :value="option">{{ option }}</option>
                                </select>

                                <input
                                    v-else
                                    v-model="form.reponses[question.id]"
                                    type="number"
                                    min="1"
                                    max="5"
                                    class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]"
                                />
                            </div>

                            <button type="button" class="w-full rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="submitResponse">
                                Soumettre ma réponse
                            </button>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Réponses individuelles</h2>
                        <div class="mt-5 space-y-4">
                            <div
                                v-for="response in responses"
                                :key="response.id"
                                class="rounded-2xl border border-slate-200 p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ response.user.name || 'Participant' }}</p>
                                        <p class="text-sm text-slate-500">{{ response.user.email }}</p>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ formatDate(response.created_at) }}</span>
                                </div>

                                <div class="mt-4 space-y-2 text-sm text-slate-700">
                                    <div v-for="(value, key) in response.reponses" :key="key" class="rounded-xl bg-slate-50 px-3 py-2">
                                        <span class="font-semibold text-slate-900">{{ key }} :</span> {{ value }}
                                    </div>
                                </div>
                            </div>

                            <p v-if="responses.length === 0" class="text-sm text-slate-500">Aucune réponse individuelle pour le moment.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>