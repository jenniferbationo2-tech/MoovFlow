<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    participants: {
        type: Array,
        default: () => [],
    },
    templates: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    objet: '',
    contenu: '',
    mode_destinataires: 'participants_evenement',
    emails_personnalises_text: '',
    date_planification: '',
});

const previewContent = computed(() => form.contenu || 'Le contenu de votre email apparaîtra ici.');

const selectedRecipients = computed(() => {
    if (form.mode_destinataires !== 'liste_personnalisee') {
        return props.participants.map((participant) => participant.email);
    }

    return form.emails_personnalises_text
        .split(/[\n,;]+/)
        .map((email) => email.trim())
        .filter(Boolean);
});

const applyTemplate = (template) => {
    form.objet = template.objet;
    form.contenu = template.contenu;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        emails_personnalises: data.emails_personnalises_text
            .split(/[\n,;]+/)
            .map((email) => email.trim())
            .filter(Boolean),
    })).post(route('communication.campaigns.send', props.evenement.id));
};
</script>

<template>
    <Head :title="`Nouvelle campagne - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nouvelle campagne email</h1>
                <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · préparation, ciblage et aperçu de l’email.</p>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
            <div class="space-y-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Objet de l’email</label>
                        <input v-model="form.objet" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mode de destinataires</label>
                        <select v-model="form.mode_destinataires" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="tous_participants">Tous les participants</option>
                            <option value="participants_evenement">Participants de l’événement</option>
                            <option value="liste_personnalisee">Liste personnalisée</option>
                        </select>
                    </div>

                    <div v-if="form.mode_destinataires === 'liste_personnalisee'">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Emails personnalisés</label>
                        <textarea
                            v-model="form.emails_personnalises_text"
                            rows="5"
                            placeholder="email1@exemple.com; email2@exemple.com"
                            class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Contenu de l’email</label>
                        <textarea v-model="form.contenu" rows="12" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Planifier l’envoi</label>
                        <input v-model="form.date_planification" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Envoyer / planifier
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Templates disponibles</h2>
                    <div class="mt-4 space-y-3">
                        <button
                            v-for="template in templates"
                            :key="template.id"
                            type="button"
                            class="w-full rounded-2xl border border-slate-200 p-4 text-left transition hover:border-[#0066B3] hover:bg-slate-50"
                            @click="applyTemplate(template)"
                        >
                            <p class="font-semibold text-slate-900">{{ template.nom }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ template.objet }}</p>
                        </button>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Preview email</h2>
                    <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Objet</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ form.objet || 'Objet de votre campagne' }}</p>
                        <div class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ previewContent }}</div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-semibold text-slate-700">Destinataires estimés</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ selectedRecipients.length }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>