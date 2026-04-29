<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => [],
    },
    questionTypes: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    titre: '',
    type: props.types[0]?.value ?? 'sondage',
    questions: [
        {
            id: `question_${Date.now()}`,
            label: '',
            type: props.questionTypes[0]?.value ?? 'texte',
            optionsText: '',
        },
    ],
});

const addQuestion = () => {
    form.questions.push({
        id: `question_${Date.now()}_${form.questions.length + 1}`,
        label: '',
        type: props.questionTypes[0]?.value ?? 'texte',
        optionsText: '',
    });
};

const removeQuestion = (index) => {
    if (form.questions.length === 1) {
        return;
    }

    form.questions.splice(index, 1);
};

const previewQuestions = computed(() => form.questions.map((question) => ({
    ...question,
    options: question.optionsText.split(/[\n,;]+/).map((option) => option.trim()).filter(Boolean),
})));

const submit = () => {
    form.transform((data) => ({
        ...data,
        questions: data.questions.map((question) => ({
            id: question.id,
            label: question.label,
            type: question.type,
            options: question.optionsText.split(/[\n,;]+/).map((option) => option.trim()).filter(Boolean),
        })),
    })).post(route('communication.enquetes.store', props.evenement.id));
};
</script>

<template>
    <Head :title="`Créer enquête - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Créer une enquête</h1>
                <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · builder dynamique des questions.</p>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
            <div class="space-y-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                    <input v-model="form.titre" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Type d’enquête</label>
                    <select v-model="form.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(question, index) in form.questions"
                        :key="question.id"
                        class="rounded-3xl border border-slate-200 p-5"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">Question {{ index + 1 }}</h2>
                            <button type="button" class="text-sm font-semibold text-red-600" @click="removeQuestion(index)">Supprimer</button>
                        </div>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Libellé</label>
                                <input v-model="question.label" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Type de question</label>
                                <select v-model="question.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                    <option v-for="type in questionTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                </select>
                            </div>

                            <div v-if="question.type === 'choix_multiple'">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Options</label>
                                <textarea
                                    v-model="question.optionsText"
                                    rows="4"
                                    placeholder="Très satisfait; Satisfait; Peu satisfait"
                                    class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="addQuestion">
                        Ajouter une question
                    </button>
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="submit">
                        Enregistrer l’enquête
                    </button>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Preview enquête</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">{{ form.titre || 'Titre de l’enquête' }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ form.type }}</p>
                    </div>

                    <div
                        v-for="(question, index) in previewQuestions"
                        :key="question.id"
                        class="rounded-2xl border border-slate-200 p-4"
                    >
                        <p class="font-semibold text-slate-900">{{ index + 1 }}. {{ question.label || 'Question sans libellé' }}</p>
                        <div class="mt-3 text-sm text-slate-600">
                            <template v-if="question.type === 'texte'">
                                Réponse texte libre
                            </template>
                            <template v-else-if="question.type === 'note'">
                                Notation de 1 à 5
                            </template>
                            <template v-else>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li v-for="option in question.options" :key="option">{{ option }}</li>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>