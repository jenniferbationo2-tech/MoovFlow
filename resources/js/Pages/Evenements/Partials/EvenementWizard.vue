<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EtapeInformations from './Wizard/EtapeInformations.vue'
import EtapeLieuDate from './Wizard/EtapeLieuDate.vue'
import EtapeBudgetRse from './Wizard/EtapeBudgetRse.vue'
import EtapeSpecifique from './Wizard/EtapeSpecifique.vue'
import EtapeRecapitulatif from './Wizard/EtapeRecapitulatif.vue'

const props = defineProps({
    typeSelectionne: { type: Object,  required: true },
    lieux:           { type: Array,   required: true },
    evenement:       { type: Object,  default: null },
    isEdit:          { type: Boolean, default: false },
    userRole:        { type: Object,  default: () => ({}) },
})

// État wizard
const etapeActuelle = ref(1)
const totalEtapes = 5

const etapes = [
    { num: 1, titre: 'Informations',  desc: 'Titre et détails' },
    { num: 2, titre: 'Lieu & Dates',  desc: 'Quand et où' },
    { num: 3, titre: 'Budget & RSE',  desc: 'Coûts et objectifs' },
    { num: 4, titre: 'Spécifique',    desc: 'Selon le type' },
    { num: 5, titre: 'Récapitulatif', desc: 'Validation finale' },
]

// Formulaire avec TOUS les champs (commun + spécifique)
const form = useForm({
    // Type (toujours fixe)
    type_evenement_id: props.typeSelectionne.id,

    // ÉTAPE 1 : Informations
    titre:           props.evenement?.titre ?? '',
    description:     props.evenement?.description ?? '',
    visuel:          null,
    reglement_pdf:   null,

    // ÉTAPE 2 : Lieu & Dates
    lieu_id:       props.evenement?.lieu_id ?? '',
    date_debut:    props.evenement?.date_debut ?? '',
    date_fin:      props.evenement?.date_fin ?? '',
    capacite_max:  props.evenement?.capacite_max ?? null,

    // ÉTAPE 3 : Budget & RSE
    budget_previsionnel:  props.evenement?.budget_previsionnel ?? null,
    tarifs:               props.evenement?.tarifs ?? [],
    public_cible:         props.evenement?.public_cible ?? '',
    cible_beneficiaires:  props.evenement?.cible_beneficiaires ?? null,
    objectifs_principaux: props.evenement?.objectifs_principaux ?? '',

    // ÉTAPE 4 : Spécifique BARA_MOUSSO
    criteres_candidature: props.evenement?.criteres_candidature ?? '',
    domaines_acceptes:    props.evenement?.domaines_acceptes ?? [],
    dotation_principale:  props.evenement?.dotation_principale ?? null,
    nombre_laureates:     props.evenement?.nombre_laureates ?? null,
    age_min:              props.evenement?.age_min ?? null,
    age_max:              props.evenement?.age_max ?? null,

    // CONF
    programme_agenda:   props.evenement?.programme_agenda ?? '',
    conferenciers:      props.evenement?.conferenciers ?? [],
    diffusion_en_ligne: props.evenement?.diffusion_en_ligne ?? false,
    lien_zoom:          props.evenement?.lien_zoom ?? '',
    document_joint:     null,

    // SPORT
    discipline:         props.evenement?.discipline ?? '',
    categorie_age:      props.evenement?.categorie_age ?? '',
    nombre_max_equipes: props.evenement?.nombre_max_equipes ?? null,
    effectif_min:       props.evenement?.effectif_min ?? null,
    effectif_max:       props.evenement?.effectif_max ?? null,
    format_competition: props.evenement?.format_competition ?? '',
    trophees_prix:      props.evenement?.trophees_prix ?? '',

    // CHALLENGE
    thematique_challenge:  props.evenement?.thematique_challenge ?? '',
    criteres_evaluation:   props.evenement?.criteres_evaluation ?? '',
    stades_acceptes:       props.evenement?.stades_acceptes ?? [],
    dotation_totale:       props.evenement?.dotation_totale ?? null,
    date_cloture_dossiers: props.evenement?.date_cloture_dossiers ?? '',

    // FORMATION
    domaine_formation:  props.evenement?.domaine_formation ?? '',
    niveau_requis:      props.evenement?.niveau_requis ?? '',
    duree_heures:       props.evenement?.duree_heures ?? null,
    certification:      props.evenement?.certification ?? false,
    nom_certification:  props.evenement?.nom_certification ?? '',
    programme_detaille: props.evenement?.programme_detaille ?? '',
    materiel_requis:    props.evenement?.materiel_requis ?? '',

    // HACK
    theme_hackathon:          props.evenement?.theme_hackathon ?? '',
    duree_heures_hack:        props.evenement?.duree_heures_hack ?? null,
    equipe_min:               props.evenement?.equipe_min ?? null,
    equipe_max:               props.evenement?.equipe_max ?? null,
    technologies_suggerees:   props.evenement?.technologies_suggerees ?? [],
    criteres_evaluation_hack: props.evenement?.criteres_evaluation_hack ?? '',

    // SALON
    nom_salon_hote:       props.evenement?.nom_salon_hote ?? '',
    organisateur_externe: props.evenement?.organisateur_externe ?? '',
    lieu_stand:           props.evenement?.lieu_stand ?? '',
    superficie_stand:     props.evenement?.superficie_stand ?? null,
    objectifs_stand:      props.evenement?.objectifs_stand ?? '',
    objectif_prospects:   props.evenement?.objectif_prospects ?? null,
})

// ──── VALIDATION PAR ÉTAPE ────
const erreursEtape1 = computed(() => {
    const e = []
    if (!form.titre?.trim()) e.push('Le titre est requis')
    return e
})

const erreursEtape2 = computed(() => {
    const e = []
    if (!form.lieu_id) e.push('Le lieu est requis')
    if (!form.date_debut) e.push('La date de début est requise')
    if (!form.date_fin) e.push('La date de fin est requise')
    if (form.date_debut && form.date_fin && new Date(form.date_fin) < new Date(form.date_debut)) {
        e.push('La date de fin doit être après la date de début')
    }
    return e
})

const peutPasserSuivant = computed(() => {
    if (etapeActuelle.value === 1) return erreursEtape1.value.length === 0
    if (etapeActuelle.value === 2) return erreursEtape2.value.length === 0
    return true
})

// ──── NAVIGATION ────
const allerEtape = (n) => {
    // Permettre de cliquer sur le stepper UNIQUEMENT pour revenir en arrière
    if (n < etapeActuelle.value) {
        etapeActuelle.value = n
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const etapeSuivante = () => {
    if (!peutPasserSuivant.value) return
    if (etapeActuelle.value < totalEtapes) {
        etapeActuelle.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const etapePrecedente = () => {
    if (etapeActuelle.value > 1) {
        etapeActuelle.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const enregistrer = () => {
    if (props.isEdit) {
        form.transform(d => ({ ...d, _method: 'put' }))
            .post(`/evenements/${props.evenement.id}`, {
                forceFormData: true,
                onError: () => { etapeActuelle.value = 1 },
            })
    } else {
        form.post('/evenements', {
            forceFormData: true,
            onError: () => { etapeActuelle.value = 1 },
        })
    }
}


const updateField = (key, value) => {
    form[key] = value
}

</script>

<template>
    <div class="mx-auto max-w-5xl">

        <div class="mb-8 rounded-2xl bg-white p-5 shadow-card sm:p-6">
            <div class="flex items-center justify-between gap-2">
                <template v-for="(et, i) in etapes" :key="et.num">

                    <!-- Cercle + Label -->
                    <button @click="allerEtape(et.num)"
                            :disabled="et.num >= etapeActuelle"
                            class="group flex flex-1 flex-col items-center gap-2 disabled:cursor-default">

                        <div :class="['flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full font-display text-sm sm:text-base font-extrabold transition',
                            et.num < etapeActuelle ? 'bg-emerald-500 text-white' :
                            et.num === etapeActuelle ? 'bg-moov-blue text-white shadow-lg ring-4 ring-moov-blue/20' :
                            'bg-page-bg text-text-muted']">
                            <svg v-if="et.num < etapeActuelle" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ et.num }}</span>
                        </div>

                        <div class="hidden text-center sm:block">
                            <p :class="['text-xs font-bold transition',
                                et.num === etapeActuelle ? 'text-moov-blue' :
                                et.num < etapeActuelle ? 'text-emerald-600' :
                                'text-text-muted']">
                                {{ et.titre }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-text-sub">{{ et.desc }}</p>
                        </div>
                    </button>

                    <!-- Ligne de connexion -->
                    <div v-if="i < etapes.length - 1"
                         :class="['h-0.5 flex-1 transition',
                             et.num < etapeActuelle ? 'bg-emerald-500' : 'bg-border-soft']">
                    </div>
                </template>
            </div>
        </div>

        <EtapeInformations v-show="etapeActuelle === 1"
                           :form="form"
                           :erreurs="erreursEtape1"
                           :type="typeSelectionne"
                           @suivant="etapeSuivante"/>

        <EtapeLieuDate v-show="etapeActuelle === 2"
                        :form="form"
                        :lieux="lieux"
                        :erreurs="erreursEtape2"
                        @precedent="etapePrecedente"
                        @suivant="etapeSuivante"/>

        <EtapeBudgetRse v-show="etapeActuelle === 3"
                        :form="form"
                        :type="typeSelectionne"
                        @precedent="etapePrecedente"
                        @suivant="etapeSuivante"/>

        <EtapeSpecifique v-show="etapeActuelle === 4"
                         :form="form"
                         :type="typeSelectionne"
                         @precedent="etapePrecedente"
                         @suivant="etapeSuivante"/>

        <EtapeRecapitulatif v-show="etapeActuelle === 5"
                    :form="form"
                    :type="typeSelectionne"
                    :lieux="lieux"
                    :is-edit="isEdit"
                    :user-role="userRole"
                    @precedent="etapePrecedente"
                    @valider="enregistrer"/>
    </div>
</template>