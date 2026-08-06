<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    evenement:           Object,
    typesEvenement:      Array,
    lieux:               Array,
    isEdit:              Boolean,
})


const etapeActuelle = ref(1)
const totalEtapes   = 3

const etapes = [
    { num: 1, titre: 'Informations',  desc: 'Détails de l\'événement', icon: 'info' },
    { num: 2, titre: 'Budget',        desc: 'Tarifs et coûts',          icon: 'budget' },
    { num: 3, titre: 'Objectifs RSE', desc: 'Impact social attendu',    icon: 'rse' },
]

const form = useForm({
    // Étape 1 : Informations
    titre:              props.evenement?.titre ?? '',
    description:        props.evenement?.description ?? '',
    type_evenement_id:  props.evenement?.type_evenement_id ?? '',
    lieu_id:            props.evenement?.lieu_id ?? '',
    date_debut:         props.evenement?.date_debut ?? '',
    date_fin:           props.evenement?.date_fin ?? '',
    capacite_max:       props.evenement?.capacite_max ?? null,
    visuel:             null,
    reglement_pdf:      null,

    budget_previsionnel: props.evenement?.budget_previsionnel ?? null,
    tarifs:              props.evenement?.tarifs?.length
        ? props.evenement.tarifs.map(t => ({
            id: t.id,
            nom: t.nom,
            montant: t.montant,
        }))
        : [],

   
    public_cible:         props.evenement?.public_cible ?? '',
    cible_beneficiaires:  props.evenement?.cible_beneficiaires ?? null,
    objectifs_principaux: props.evenement?.objectifs_principaux ?? '',
})

const erreursEtape1 = computed(() => {
    const erreurs = []
    if (!form.titre?.trim()) erreurs.push('Le titre est requis')
    if (!form.type_evenement_id) erreurs.push('Le type d\'événement est requis')
    if (!form.lieu_id) erreurs.push('Le lieu est requis')
    if (!form.date_debut) erreurs.push('La date de début est requise')
    if (!form.date_fin) erreurs.push('La date de fin est requise')
    if (form.date_debut && form.date_fin && new Date(form.date_fin) < new Date(form.date_debut)) {
        erreurs.push('La date de fin doit être après la date de début')
    }
    return erreurs
})

const peutPasserEtape2 = computed(() => erreursEtape1.value.length === 0)

//  NAVIGATION WIZARD 
const allerEtape = (n) => {
    if (n > etapeActuelle.value && !peutPasserEtape2.value && etapeActuelle.value === 1) {
        return
    }
    etapeActuelle.value = n
}

const etapeSuivante = () => {
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

const ajouterTarif = () => {
    form.tarifs.push({ nom: '', montant: 0 })
}
const retirerTarif = (i) => {
    form.tarifs.splice(i, 1)
}

//  VISUEL & PDF 
const previewVisuel = ref(props.evenement?.visuel_url ?? null)
const onVisuelChange = (e) => {
    const file = e.target.files[0]
    form.visuel = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => previewVisuel.value = ev.target.result
        reader.readAsDataURL(file)
    }
}

const reglementNouveauNom = ref(null)
const reglementActuel = computed(() => props.evenement?.reglement_pdf_url)
const onReglementChange = (e) => {
    const file = e.target.files[0]
    form.reglement_pdf = file
    reglementNouveauNom.value = file?.name ?? null
}


const enregistrer = () => {
    if (props.isEdit) {
        // Pour update avec multipart, on utilise la méthode POST avec _method=put
        form.transform(d => ({ ...d, _method: 'put' }))
            .post(`/evenements/${props.evenement.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onError: () => {
                    // Si erreur, retourner à l'étape 1 pour voir
                    etapeActuelle.value = 1
                    window.scrollTo({ top: 0, behavior: 'smooth' })
                },
            })
    } else {
        form.post('/evenements', {
            forceFormData: true,
            onError: () => {
                etapeActuelle.value = 1
                window.scrollTo({ top: 0, behavior: 'smooth' })
            },
        })
    }
}

// DÉTECTION TYPE POUR INFO 
const typeSelectionne = computed(() =>
    props.typesEvenement?.find(t => t.id === Number(form.type_evenement_id))
)
</script>

<template>
    <div class="mx-auto max-w-5xl">

        
        <div class="mb-8 rounded-2xl bg-card p-6 shadow-card">
            <div class="flex items-center justify-between">
                <div v-for="(et, i) in etapes" :key="et.num" class="flex flex-1 items-center">
                    <!-- Cercle étape -->
                    <button @click="allerEtape(et.num)"
                            :disabled="et.num > etapeActuelle && !peutPasserEtape2 && etapeActuelle === 1"
                            class="group flex flex-col items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <div :class="['flex h-12 w-12 items-center justify-center rounded-full font-display text-base font-extrabold transition',
                            et.num < etapeActuelle ? 'bg-emerald-500 text-white' :
                            et.num === etapeActuelle ? 'bg-moov-blue text-white shadow-lg ring-4 ring-moov-blue/20' :
                            'bg-page-bg text-text-muted']">
                            <svg v-if="et.num < etapeActuelle" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ et.num }}</span>
                        </div>
                        <div class="text-center">
                            <p :class="['text-sm font-bold transition',
                                et.num === etapeActuelle ? 'text-moov-blue' :
                                et.num < etapeActuelle ? 'text-emerald-600' :
                                'text-text-muted']">
                                {{ et.titre }}
                            </p>
                            <p class="mt-0.5 text-xs text-text-sub">{{ et.desc }}</p>
                        </div>
                    </button>

                    <!-- Ligne entre étapes -->
                    <div v-if="i < etapes.length - 1"
                         :class="['mx-4 h-0.5 flex-1 transition',
                             et.num < etapeActuelle ? 'bg-emerald-500' : 'bg-border-soft']">
                    </div>
                </div>
            </div>
        </div>

        <!--  ÉTAPE 1 : INFORMATIONS  -->
        <div v-if="etapeActuelle === 1" class="rounded-2xl bg-card shadow-card">

            <div class="border-b border-border-soft p-6">
                <h2 class="font-display text-2xl font-extrabold text-text-main">
                    Informations de l'événement
                </h2>
                <p class="mt-1 text-sm text-text-sub">
                    Renseignez les détails clés de votre événement
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 p-6 lg:grid-cols-3">

                <!-- COLONNE GAUCHE (formulaire) -->
                <div class="space-y-5 lg:col-span-2">

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Titre de l'événement *
                        </label>
                        <input v-model="form.titre" type="text" required
                               placeholder="Ex: Hackathon Moov 2026 - 48h pour innover"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        <p v-if="form.errors.titre" class="mt-1 text-xs text-red-600">{{ form.errors.titre }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Description
                        </label>
                        <textarea v-model="form.description" rows="5"
                                  placeholder="Décrivez l'événement, ses objectifs, son public cible..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Type d'événement *
                            </label>
                            <select v-model="form.type_evenement_id" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                <option value="">Sélectionner un type</option>
                                <option v-for="t in typesEvenement" :key="t.id" :value="t.id">
                                    {{ t.nom }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Lieu *
                            </label>
                            <select v-model="form.lieu_id" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                <option value="">Sélectionner un lieu</option>
                                <option v-for="l in lieux" :key="l.id" :value="l.id">
                                    {{ l.nom }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Date de début *
                            </label>
                            <input v-model="form.date_debut" type="datetime-local" required
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Date de fin *
                            </label>
                            <input v-model="form.date_fin" type="datetime-local" required
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Capacité maximale
                        </label>
                        <input v-model.number="form.capacite_max" type="number" min="0" step="1"
                               placeholder="Nombre max de participants"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        <p class="mt-1 text-xs text-text-muted">
                            Laisser vide pour illimité
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Règlement de l'événement (PDF)
                        </label>
                        <div class="rounded-lg border-2 border-dashed border-border-soft bg-page-bg/30 p-4">
                            <input @change="onReglementChange" type="file" accept="application/pdf"
                                   class="block w-full text-sm text-text-sub file:mr-3 file:rounded-lg file:border-0 file:bg-moov-blue file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:tracking-wider file:text-white"/>
                            <p v-if="reglementNouveauNom" class="mt-2 flex items-center gap-2 text-xs text-emerald-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Nouveau : {{ reglementNouveauNom }}
                            </p>
                            <p v-else-if="reglementActuel" class="mt-2 flex items-center gap-2 text-xs text-text-sub">
                                <a :href="reglementActuel" target="_blank" class="font-bold text-moov-blue hover:underline">
                                    Voir le règlement actuel
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Visuel de l'événement
                    </label>
                    <label class="group block cursor-pointer">
                        <div :class="['relative flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition',
                            previewVisuel ? 'border-moov-blue' : 'border-border-soft bg-page-bg hover:bg-page-bg/70']">

                            <img v-if="previewVisuel"
                                 :src="previewVisuel"
                                 alt="Aperçu"
                                 class="h-full w-full object-cover"/>

                            <div v-else class="text-center">
                                <svg class="mx-auto h-10 w-10 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm font-bold text-text-sub">Déposer une image</p>
                                <p class="text-xs text-text-muted">PNG, JPG, WEBP — Max 5 Mo</p>
                            </div>
                        </div>
                        <input @change="onVisuelChange" type="file" accept="image/*" class="hidden"/>
                    </label>

                    <!-- Aide contextuelle selon le type -->
                    <div v-if="typeSelectionne" class="mt-4 rounded-xl bg-blue-50 p-4 text-xs">
                        <p class="font-bold text-blue-900">{{ typeSelectionne.nom }}</p>
                        <p class="mt-1 text-blue-700">
                            {{ ({
                                BARA_MOUSSO: 'Concours valorisant les femmes entrepreneures du Burkina',
                                CONF: 'Conférence professionnelle ouverte au public ciblé',
                                SPORT: 'Tournoi avec équipes, phases et classement automatique',
                                CHALLENGE: 'Challenge d\'innovation avec sélection de projets',
                                FORMATION: 'Formation numérique avec niveaux et certifications',
                                HACK: 'Hackathon collaboratif sur 24-48h',
                                SALON: 'Présence Moov sur un événement externe (vitrine)',
                            })[typeSelectionne.code] || 'Type d\'événement standard' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Erreurs validation étape 1 -->
            <div v-if="erreursEtape1.length > 0 && etapeActuelle === 1"
                 class="border-t border-border-soft bg-red-50 p-4">
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-red-700">
                    Champs requis manquants :
                </p>
                <ul class="space-y-1 text-sm text-red-600">
                    <li v-for="(err, i) in erreursEtape1" :key="i" class="flex items-center gap-2">
                        <span class="h-1 w-1 rounded-full bg-red-600"></span>
                        {{ err }}
                    </li>
                </ul>
            </div>

            <!-- Footer navigation -->
            <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Étape 1 / {{ totalEtapes }}
                </p>
                <button @click="etapeSuivante"
                        :disabled="!peutPasserEtape2"
                        class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark disabled:cursor-not-allowed disabled:opacity-50">
                    Suivant : Budget
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- ════════════ ÉTAPE 2 : BUDGET ════════════ -->
        <div v-if="etapeActuelle === 2" class="rounded-2xl bg-card shadow-card">

            <div class="border-b border-border-soft p-6">
                <h2 class="font-display text-2xl font-extrabold text-text-main">
                    Budget & Tarifs
                </h2>
                <p class="mt-1 text-sm text-text-sub">
                    Définissez le budget et les tarifs d'inscription
                </p>
            </div>

            <div class="space-y-6 p-6">

                
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Budget prévisionnel total (FCFA)
                    </label>
                    <div class="relative">
                        <input v-model.number="form.budget_previsionnel" type="number" min="0" step="1"
                               placeholder="Ex: 5000000"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 pr-16 text-base font-medium outline-none transition focus:border-moov-blue"/>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-muted">FCFA</span>
                    </div>
                    <p v-if="form.budget_previsionnel > 0" class="mt-1 text-xs text-text-sub">
                        Soit <strong>{{ Number(form.budget_previsionnel).toLocaleString('fr-FR') }}</strong> FCFA
                    </p>
                </div>

                <!-- Tarifs dynamiques -->
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-sub">
                                Tarifs d'inscription
                            </p>
                            <p class="mt-0.5 text-xs text-text-muted">
                                Définissez plusieurs tarifs si nécessaire (Standard, VIP, Réduit...)
                            </p>
                        </div>
                        <button @click="ajouterTarif" type="button"
                                class="flex items-center gap-1.5 rounded-lg bg-moov-noir px-3 py-2 text-xs font-bold text-white transition hover:bg-moov-noir-soft">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter
                        </button>
                    </div>

                    <div v-if="form.tarifs.length === 0"
                         class="rounded-xl border-2 border-dashed border-border-soft bg-page-bg/30 p-6 text-center">
                        <p class="text-sm font-bold text-text-sub">Aucun tarif défini</p>
                        <p class="mt-1 text-xs text-text-muted">Cliquez sur "Ajouter" pour créer un tarif</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="(t, i) in form.tarifs" :key="i"
                             class="flex items-end gap-3 rounded-xl border border-border-soft bg-white p-4">
                            <div class="flex-1">
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                    Nom du tarif
                                </label>
                                <input v-model="t.nom" type="text" placeholder="Ex: Standard, VIP, Étudiant..."
                                       class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div class="w-40">
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                    Montant (FCFA)
                                </label>
                                <input v-model.number="t.montant" type="number" min="0" step="1"
                                       class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-3 py-2 text-right text-sm font-bold outline-none focus:border-moov-blue"/>
                            </div>
                            <button @click="retirerTarif(i)" type="button"
                                    class="rounded-lg bg-red-50 p-2.5 text-red-600 transition hover:bg-red-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer navigation -->
            <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
                <button @click="etapePrecedente"
                        class="flex items-center gap-2 rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour
                </button>

                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Étape 2 / {{ totalEtapes }}
                </p>

                <button @click="etapeSuivante"
                        class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark">
                    Suivant : Objectifs RSE
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>

       <!-- ════════════ ÉTAPE 3 : OBJECTIFS & CIBLES ════════════ -->
<div v-if="etapeActuelle === 3" class="rounded-2xl bg-card shadow-card">

    <div class="border-b border-border-soft p-6">
        <h2 class="font-display text-2xl font-extrabold text-text-main">
            Cibles & Objectifs
        </h2>
        <p class="mt-1 text-sm text-text-sub">
            Définissez le public visé et vos objectifs pour cet événement
        </p>
    </div>

    <div class="space-y-6 p-6">

        <!-- Info contextuelle -->
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-bold text-blue-900">Bonne pratique RSE</p>
                    <p class="mt-1 text-blue-800">
                        Ces informations servent à <strong>cadrer votre événement</strong> en amont.
                        Le <strong>bilan d'impact réel</strong> (chiffres exacts, photos, témoignages)
                        sera renseigné après l'événement, dans la page <strong>"Bilan & Impact"</strong>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Public cible -->
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                Public cible *
            </label>
            <input v-model="form.public_cible" type="text" required
                   placeholder="Ex: Femmes entrepreneures, Jeunes 18-25 ans, Étudiants en informatique..."
                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
            <p class="mt-1 text-xs text-text-muted">
                Décrivez en quelques mots qui vous voulez toucher
            </p>
        </div>

        <!-- Cible chiffrée -->
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                Cible de bénéficiaires (estimation)
            </label>
            <div class="relative">
                <input v-model.number="form.cible_beneficiaires" type="number" min="0" step="1"
                       placeholder="Ex: 200"
                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 pr-32 text-base font-medium outline-none transition focus:border-moov-blue"/>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-muted">
                    personnes
                </span>
            </div>
            <p class="mt-1 text-xs text-text-muted">
                Combien de personnes espérez-vous toucher ? (Une estimation suffit)
            </p>
        </div>

        <!-- Objectifs -->
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                Objectifs principaux *
            </label>
            <textarea v-model="form.objectifs_principaux" rows="6" required
                      placeholder="Ex:&#10;• Sensibiliser 200 jeunes aux métiers du numérique&#10;• Identifier 5 talents pour stage chez Moov&#10;• Distribuer 100 bourses de formation&#10;• Créer 10 partenariats avec des écoles..."
                      class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
            <p class="mt-1 text-xs text-text-muted">
                Quels résultats concrets vous attendez de cet événement ?
            </p>
        </div>

        <!-- Aperçu récapitulatif -->
        <div class="rounded-xl bg-page-bg p-5">
            <p class="mb-3 font-display text-base font-extrabold text-text-main">
                📋 Récapitulatif de votre événement
            </p>
            <div class="space-y-2 text-sm">
                <p><span class="font-bold text-text-sub">Titre :</span> {{ form.titre || '—' }}</p>
                <p><span class="font-bold text-text-sub">Public cible :</span> {{ form.public_cible || '—' }}</p>
                <p><span class="font-bold text-text-sub">Cible :</span>
                    {{ form.cible_beneficiaires ? form.cible_beneficiaires + ' personnes' : '—' }}
                </p>
                <p><span class="font-bold text-text-sub">Budget :</span>
                    {{ form.budget_previsionnel ? Number(form.budget_previsionnel).toLocaleString('fr-FR') + ' FCFA' : '—' }}
                </p>
                <p><span class="font-bold text-text-sub">Tarifs :</span>
                    {{ form.tarifs.length || 0 }} tarif(s) défini(s)
                </p>
            </div>
        </div>

        <!-- Info finale -->
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-bold text-amber-900">Statut après création</p>
                    <p class="mt-1 text-amber-800">
                        L'événement sera créé en <strong>statut Brouillon</strong>.
                        Une fois prêt, demandez la validation au responsable dCIRP pour publication.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer navigation -->
    <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
        <button @click="etapePrecedente"
                class="flex items-center gap-2 rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </button>

        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
            Étape 3 / {{ totalEtapes }}
        </p>

        <button @click="enregistrer"
                :disabled="form.processing"
                class="flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50">
            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            {{ form.processing ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Créer l\'événement') }}
        </button>
    </div>
</div>

    </div>
</template>