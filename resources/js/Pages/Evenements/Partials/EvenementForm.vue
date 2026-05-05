<script setup>
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import TabPanel from '@/Components/TabPanel.vue';
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    evenement: {
        type: Object,
        default: null,
    },
    typesEvenement: {
        type: Array,
        default: () => [],
    },
    lieux: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    submitUrl: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'post',
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    submitLabel: {
        type: String,
        default: 'Enregistrer',
    },
});

const activeTab = ref('informations');
const previewUrl = ref(props.evenement?.visuel_url ?? null);
const reglementActuel = ref(props.evenement?.reglement_pdf_url ?? null);
const reglementNouveauNom = ref(null);
const showPublishModal = ref(false);

const tabs = [
    { label: 'Informations', value: 'informations' },
    { label: 'Budget', value: 'budget' },
    { label: 'Objectifs RSE', value: 'rse' },
];

const form = useForm({
    _method: props.method === 'patch' ? 'patch' : undefined,
    titre: props.evenement?.titre ?? '',
    description: props.evenement?.description ?? '',
    visuel: null,
    reglement_pdf: null,
    type_evenement_id: props.evenement?.type_evenement_id ?? '',
    date_debut: props.evenement?.date_debut ?? '',
    date_fin: props.evenement?.date_fin ?? '',
    lieu_id: props.evenement?.lieu_id ?? '',
    statut: props.evenement?.statut ?? 'brouillon',
    montant_previsionnel: props.evenement?.montant_previsionnel ?? 0,
    devise: props.evenement?.devise ?? 'XOF',
    type_impact: props.evenement?.type_impact ?? '',
    nb_beneficiaires_cibles: props.evenement?.nb_beneficiaires_cibles ?? 0,
    nb_beneficiaires_indirects: props.evenement?.nb_beneficiaires_indirects ?? 0,
    nb_associations_soutenues: props.evenement?.nb_associations_soutenues ?? 0,
    nb_projets_accompagnes: props.evenement?.nb_projets_accompagnes ?? 0,
    nb_femmes_beneficiaires: props.evenement?.nb_femmes_beneficiaires ?? 0,
    montants_collectes: props.evenement?.montants_collectes ?? 0,
    retombees_partenaires: props.evenement?.retombees_partenaires ?? 0,
    nb_emplois_crees: props.evenement?.nb_emplois_crees ?? 0,
    score_environnemental: props.evenement?.score_environnemental ?? 0,
});

const formMethod = computed(() => (props.method === 'patch' ? form.post : form.post));

const handleFileChange = (event) => {
    const [file] = event.target.files;
    form.visuel = file ?? null;
    previewUrl.value = file ? URL.createObjectURL(file) : props.evenement?.visuel_url ?? null;
};

const handleReglementChange = (event) => {
    const [file] = event.target.files;
    form.reglement_pdf = file ?? null;
    reglementNouveauNom.value = file?.name ?? null;
};

const submit = (status) => {
    form.statut = status;
    formMethod.value(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const publish = () => {
    showPublishModal.value = false;
    submit('publie');
};
</script>

<template>
    <div class="space-y-6">
        <TabPanel :tabs="tabs" v-model:active-tab="activeTab">
            <template #default="{ activeTab: currentTab }">
                <div v-show="currentTab === 'informations'" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="grid gap-6 lg:grid-cols-[1.6fr,1fr]">
                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                                <input v-model="form.titre" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                                <InputError class="mt-2" :message="form.errors.titre" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                                <textarea v-model="form.description" rows="6" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Type d'événement</label>
                                    <select v-model="form.type_evenement_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                        <option value="">Sélectionner</option>
                                        <option v-for="type in typesEvenement" :key="type.id" :value="type.id">{{ type.nom }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.type_evenement_id" />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Lieu</label>
                                    <select v-model="form.lieu_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                        <option value="">Sélectionner</option>
                                        <option v-for="lieu in lieux" :key="lieu.id" :value="lieu.id">{{ lieu.nom }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.lieu_id" />
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date de début</label>
                                    <input v-model="form.date_debut" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                                    <InputError class="mt-2" :message="form.errors.date_debut" />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date de fin</label>
                                    <input v-model="form.date_fin" type="datetime-local" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                                    <InputError class="mt-2" :message="form.errors.date_fin" />
                                </div>
                            </div>

                            <!-- 🆕 RÈGLEMENT PDF -->
                            <div class="rounded-2xl border-2 border-amber-200 bg-amber-50/50 p-5">
                                <div class="mb-3 flex items-start gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-100">
                                        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-amber-900">Règlement de l'événement</h3>
                                        <p class="mt-0.5 text-xs text-amber-800">
                                            Document PDF contenant les règles, consignes et critères de participation.
                                            Sera téléchargeable par les candidats avant inscription.
                                        </p>
                                    </div>
                                </div>

                                <!-- Si un règlement existe déjà -->
                                <div v-if="reglementActuel && !reglementNouveauNom"
                                     class="mb-3 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <a :href="reglementActuel" target="_blank"
                                       class="flex-1 text-sm font-bold text-emerald-800 underline hover:text-emerald-900">
                                        Voir le règlement actuel
                                    </a>
                                    <span class="text-xs text-emerald-700">Choisissez un nouveau fichier pour remplacer</span>
                                </div>

                                <!-- Si nouveau fichier sélectionné -->
                                <div v-if="reglementNouveauNom"
                                     class="mb-3 flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="flex-1 text-sm font-bold text-blue-800">
                                        Nouveau fichier : {{ reglementNouveauNom }}
                                    </p>
                                </div>

                                <!-- Input file -->
                                <input type="file"
                                       accept="application/pdf"
                                       @change="handleReglementChange"
                                       class="block w-full cursor-pointer rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-amber-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-amber-800 hover:file:bg-amber-200"/>

                                <p class="mt-2 text-xs text-amber-700">
                                    Format accepté : PDF · Taille maximale : 10 MB · Optionnel
                                </p>

                                <InputError class="mt-2" :message="form.errors.reglement_pdf" />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="block text-sm font-semibold text-slate-700">Visuel</label>
                            <label class="flex min-h-72 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-[#0066B3]/30 bg-gradient-to-br from-[#0066B3]/5 to-[#00A651]/5 p-6 text-center transition hover:border-[#0066B3]">
                                <template v-if="previewUrl">
                                    <img :src="previewUrl" alt="Aperçu du visuel" class="h-56 w-full rounded-2xl object-cover shadow-sm" />
                                </template>
                                <template v-else>
                                    <div class="space-y-2">
                                        <p class="text-sm font-semibold text-slate-700">Déposer une image ou cliquer pour sélectionner</p>
                                        <p class="text-xs text-slate-500">PNG, JPG ou WEBP jusqu'à 5 Mo</p>
                                    </div>
                                </template>
                                <input type="file" class="hidden" accept="image/*" @change="handleFileChange" />
                            </label>
                            <InputError class="mt-2" :message="form.errors.visuel" />
                        </div>
                    </div>
                </div>

                <div v-show="currentTab === 'budget'" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Montant prévisionnel</label>
                            <input v-model="form.montant_previsionnel" type="number" min="0" step="0.01" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                            <InputError class="mt-2" :message="form.errors.montant_previsionnel" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Devise</label>
                            <input v-model="form.devise" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                            <InputError class="mt-2" :message="form.errors.devise" />
                        </div>
                    </div>
                </div>

                <div v-show="currentTab === 'rse'" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Type d'impact</label>
                            <input v-model="form.type_impact" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                            <InputError class="mt-2" :message="form.errors.type_impact" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre de bénéficiaires cibles</label>
                            <input v-model="form.nb_beneficiaires_cibles" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                            <InputError class="mt-2" :message="form.errors.nb_beneficiaires_cibles" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Bénéficiaires indirects</label>
                            <input v-model="form.nb_beneficiaires_indirects" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Associations soutenues</label>
                            <input v-model="form.nb_associations_soutenues" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Projets accompagnés</label>
                            <input v-model="form.nb_projets_accompagnes" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Femmes bénéficiaires</label>
                            <input v-model="form.nb_femmes_beneficiaires" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Montants collectés</label>
                            <input v-model="form.montants_collectes" type="number" min="0" step="0.01" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Retombées partenaires</label>
                            <input v-model="form.retombees_partenaires" type="number" min="0" step="0.01" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Emplois créés</label>
                            <input v-model="form.nb_emplois_crees" type="number" min="0" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Score environnemental</label>
                            <input v-model="form.score_environnemental" type="number" min="0" max="100" step="0.01" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>
                    </div>
                </div>
            </template>
        </TabPanel>

        <div class="flex flex-col gap-3 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:flex-row md:items-center md:justify-between">
            <a :href="cancelUrl" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Annuler
            </a>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    class="rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900"
                    :disabled="form.processing"
                    @click="submit('brouillon')"
                >
                    Enregistrer brouillon
                </button>
                <button
                    type="button"
                    class="rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]"
                    :disabled="form.processing"
                    @click="showPublishModal = true"
                >
                    {{ submitLabel === 'Sauvegarder' ? 'Sauvegarder et publier' : 'Publier' }}
                </button>
            </div>
        </div>

        <Modal
            :show="showPublishModal"
            title="Publier l'événement"
            description="Cette action rendra l'événement visible dans le planning et son statut passera à publié."
            confirm-text="Publier maintenant"
            cancel-text="Revenir au formulaire"
            @close="showPublishModal = false"
            @confirm="publish"
        />
    </div>
</template>