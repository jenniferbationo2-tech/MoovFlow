<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:               Object,
    equipes:                 Array,
    phases:                  Array,
    classements:             Object,
    participantsDisponibles: { type: Array, default: () => [] },
})

// ── ONGLETS ──────────────────────────────
const ongletActif = ref('equipes')
const onglets = [
    { value: 'equipes',     label: 'Équipes',              icon: '👥' },
    { value: 'phases',      label: 'Phases & Rencontres',  icon: '⚔' },
    { value: 'classements', label: 'Classement',           icon: '🏆' },
]

// ── HELPERS ──────────────────────────────
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const formaterHeure = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
    })
}

const modalEquipeOuvert = ref(false)
const formEquipe = useForm({
    nom: '',
    capitaine: '',
    categorie: '',
})

const ouvrirModalEquipe = () => {
    formEquipe.reset()
    modalEquipeOuvert.value = true
}

const creerEquipe = () => {
    formEquipe.post(`/evenements/${props.evenement.id}/competition/equipes`, {
        onSuccess: () => modalEquipeOuvert.value = false,
        preserveScroll: true,
    })
}

const supprimerEquipe = (equipe) => {
    if (confirm(`Supprimer l'équipe "${equipe.nom}" ?`)) {
        router.delete(`/evenements/${props.evenement.id}/competition/equipes/${equipe.id}`)
    }
}

// ── MEMBRES D'ÉQUIPE (à partir des participants inscrits) ──
const equipeMembreOuverte = ref(null)
const formMembre = useForm({
    user_id: '',
    role: 'membre',
})

const ouvrirAjoutMembre = (equipe) => {
    formMembre.reset()
    equipeMembreOuverte.value = equipe.id
}

const ajouterMembre = (equipe) => {
    formMembre.post(`/evenements/${props.evenement.id}/competition/equipes/${equipe.id}/membres`, {
        onSuccess: () => equipeMembreOuverte.value = null,
        preserveScroll: true,
    })
}

const retirerMembre = (equipe, membre) => {
    if (confirm(`Retirer ${membre.user?.nom_complet ?? 'ce participant'} de l'équipe ?`)) {
        router.delete(`/evenements/${props.evenement.id}/competition/equipes/${equipe.id}/membres/${membre.id}`, {
            preserveScroll: true,
        })
    }
}



const modalPhaseOuvert = ref(false)
const formPhase = useForm({
    nom: '',
    ordre: 1,
    date_debut: '',
    date_fin: '',
})

const ouvrirModalPhase = () => {
    formPhase.reset()
    formPhase.ordre = (props.phases?.length ?? 0) + 1
    modalPhaseOuvert.value = true
}

const creerPhase = () => {
    formPhase.post(`/evenements/${props.evenement.id}/competition/phases`, {
        onSuccess: () => modalPhaseOuvert.value = false,
        preserveScroll: true,
    })
}

const supprimerPhase = (phase) => {
    if (confirm(`Supprimer la phase "${phase.nom}" ?`)) {
        router.delete(`/evenements/${props.evenement.id}/competition/phases/${phase.id}`)
    }
}



const phaseRencontre = ref(null)
const modalRencontreOuvert = ref(false)
const formRencontre = useForm({
    equipe_a_id: '',
    equipe_b_id: '',
    date_match: '',
    lieu_match: '',
    arbitre: '',
})

const ouvrirModalRencontre = (phase) => {
    phaseRencontre.value = phase
    formRencontre.reset()
    modalRencontreOuvert.value = true
}

const creerRencontre = () => {
    formRencontre.post(`/evenements/${props.evenement.id}/competition/phases/${phaseRencontre.value.id}/rencontres`, {
        onSuccess: () => modalRencontreOuvert.value = false,
        preserveScroll: true,
    })
}

const rencontreScore = ref(null)
const formScore = useForm({
    score_equipe_a: 0,
    score_equipe_b: 0,
    observations: '',
})

const ouvrirModalScore = (rencontre) => {
    rencontreScore.value = rencontre
    formScore.score_equipe_a = rencontre.score_equipe_a ?? 0
    formScore.score_equipe_b = rencontre.score_equipe_b ?? 0
    formScore.observations = rencontre.observations ?? ''
}

const enregistrerScore = () => {
    formScore.patch(`/evenements/${props.evenement.id}/competition/rencontres/${rencontreScore.value.id}/score`, {
        onSuccess: () => rencontreScore.value = null,
        preserveScroll: true,
    })
}


const couleurStatutRencontre = (statut) => ({
    planifiee: { bg: 'bg-amber-50', text: 'text-amber-700', label: 'Planifiée' },
    en_cours:  { bg: 'bg-blue-50',  text: 'text-blue-700',  label: 'En cours' },
    terminee:  { bg: 'bg-emerald-50', text: 'text-emerald-700', label: 'Terminée' },
    reportee:  { bg: 'bg-slate-100', text: 'text-slate-600', label: 'Reportée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', label: statut })

const phasesActives = computed(() => props.phases ?? [])

// ── GÉNÉRATION AUTOMATIQUE DE LA PHASE SUIVANTE ──
const gagnantsPhase = (phase) => (phase.rencontres ?? []).map(r => r.vainqueur).filter(Boolean)

const phaseComplete = (phase) =>
    (phase.rencontres?.length ?? 0) > 0 && phase.rencontres.every(r => r.statut === 'terminee')

const phaseAvecNul = (phase) =>
    (phase.rencontres ?? []).some(r => r.statut === 'terminee' && !r.vainqueur)

const pairesSuivantes = (phase) => {
    const g = gagnantsPhase(phase)
    const paires = []
    for (let i = 0; i + 1 < g.length; i += 2) paires.push([g[i], g[i + 1]])
    return paires
}

const equipeQualifieeSansAdversaire = (phase) => {
    const g = gagnantsPhase(phase)
    return g.length % 2 === 1 ? g[g.length - 1] : null
}

const suggererNomPhase = (nbPaires) => {
    const nbEquipes = nbPaires * 2
    if (nbEquipes === 2) return 'Finale'
    if (nbEquipes === 4) return 'Demi-finales'
    if (nbEquipes === 8) return 'Quarts de finale'
    if (nbEquipes === 16) return 'Huitièmes de finale'
    return 'Phase suivante'
}

const modalGenerationOuvert = ref(false)
const phaseGeneration = ref(null)
const pairesGeneration = ref([])
const formGeneration = useForm({
    nom: '',
    dates: [],
    lieu_match: '',
})

const ouvrirModalGeneration = (phase) => {
    phaseGeneration.value = phase
    pairesGeneration.value = pairesSuivantes(phase)
    formGeneration.reset()
    formGeneration.nom = suggererNomPhase(pairesGeneration.value.length)
    formGeneration.dates = pairesGeneration.value.map(() => '')
    modalGenerationOuvert.value = true
}

const genererPhaseSuivante = () => {
    formGeneration.post(`/evenements/${props.evenement.id}/competition/phases/${phaseGeneration.value.id}/generer-suivante`, {
        onSuccess: () => modalGenerationOuvert.value = false,
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6">
            <Link :href="`/evenements/${evenement.id}`"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-text-sub hover:text-moov-blue">
                ← Retour à l'événement
            </Link>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-text-main">
                        Compétition
                    </h1>
                    <p class="mt-1 text-sm text-text-sub">
                        {{ evenement.titre }}
                        <span v-if="evenement.lieu"> · {{ evenement.lieu.nom }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ── KPIs ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Équipes</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                    {{ equipes?.length ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Phases</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-violet-600">
                    {{ phases?.length ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Rencontres</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-orange-600">
                    {{ phases?.reduce((acc, p) => acc + (p.rencontres?.length ?? 0), 0) ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Matchs joués</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">
                    {{ phases?.reduce((acc, p) =>
                        acc + (p.rencontres?.filter(r => r.statut === 'terminee').length ?? 0), 0) ?? 0 }}
                </p>
            </div>
        </div>

        <!-- ── ONGLETS ── -->
        <div class="mb-6 border-b border-border-soft">
            <nav class="flex gap-1">
                <button v-for="o in onglets" :key="o.value"
                        @click="ongletActif = o.value"
                        :class="['rounded-t-lg px-5 py-3 text-sm font-bold transition',
                            ongletActif === o.value
                                ? 'bg-card text-moov-blue border-2 border-b-0 border-border-soft'
                                : 'text-text-sub hover:text-moov-blue']">
                    {{ o.label }}
                </button>
            </nav>
        </div>

        <div v-show="ongletActif === 'equipes'" class="space-y-4">

            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-text-main">
                    Équipes participantes
                </h2>
                <button @click="ouvrirModalEquipe"
                        class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvelle Équipe
                </button>
            </div>

            <!-- Liste des équipes -->
            <div v-if="equipes?.length" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="eq in equipes" :key="eq.id"
                     class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-display text-lg font-extrabold text-text-main">
                                {{ eq.nom }}
                            </h3>
                            <p v-if="eq.capitaine" class="mt-1 text-sm text-text-sub">
                                Capitaine : <span class="font-bold">{{ eq.capitaine }}</span>
                            </p>
                            <span v-if="eq.categorie"
                                  class="mt-2 inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                                {{ eq.categorie }}
                            </span>
                        </div>
                        <button @click="supprimerEquipe(eq)"
                                class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600"
                                title="Supprimer">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Membres de l'équipe (participants inscrits) -->
                    <div class="mt-4 border-t border-border-soft pt-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Membres ({{ eq.membres?.length ?? 0 }})
                            </p>
                            <button v-if="equipeMembreOuverte !== eq.id" @click="ouvrirAjoutMembre(eq)"
                                    :disabled="!participantsDisponibles?.length"
                                    class="text-xs font-bold text-moov-blue hover:underline disabled:cursor-not-allowed disabled:text-text-muted disabled:no-underline">
                                + Ajouter
                            </button>
                        </div>

                        <ul v-if="eq.membres?.length" class="mt-2 space-y-1.5">
                            <li v-for="m in eq.membres" :key="m.id"
                                class="flex items-center justify-between gap-2 text-sm">
                                <span class="min-w-0 truncate text-text-main">
                                    {{ m.user?.nom_complet ?? '—' }}
                                    <span v-if="m.role === 'capitaine'"
                                          class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                        Capitaine
                                    </span>
                                </span>
                                <button @click="retirerMembre(eq, m)"
                                        class="shrink-0 text-text-muted transition hover:text-red-600" title="Retirer">
                                    ✕
                                </button>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-xs text-text-sub">Aucun membre inscrit dans cette équipe.</p>

                        <form v-if="equipeMembreOuverte === eq.id" @submit.prevent="ajouterMembre(eq)"
                              class="mt-3 space-y-2 rounded-lg bg-page-bg/50 p-3">
                            <select v-model="formMembre.user_id" required
                                    class="w-full rounded-lg border border-border-soft px-2 py-1.5 text-sm outline-none focus:border-moov-blue">
                                <option value="">Sélectionner un participant inscrit</option>
                                <option v-for="p in participantsDisponibles" :key="p.id" :value="p.id">{{ p.nom_complet }}</option>
                            </select>
                            <p v-if="formMembre.errors.user_id" class="text-xs text-red-600">{{ formMembre.errors.user_id }}</p>
                            <select v-model="formMembre.role"
                                    class="w-full rounded-lg border border-border-soft px-2 py-1.5 text-sm outline-none focus:border-moov-blue">
                                <option value="membre">Membre</option>
                                <option value="capitaine">Capitaine</option>
                            </select>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="equipeMembreOuverte = null"
                                        class="text-xs font-bold text-text-sub hover:text-text-main">
                                    Annuler
                                </button>
                                <button type="submit" :disabled="formMembre.processing"
                                        class="rounded-lg bg-moov-blue px-3 py-1.5 text-xs font-bold text-white transition hover:bg-moov-blue-dark disabled:opacity-50">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Vide -->
            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucune équipe enregistrée</p>
                <p class="mt-1 text-sm text-text-sub">Commencez par créer la première équipe</p>
            </div>
        </div>

        <div v-show="ongletActif === 'phases'" class="space-y-4">

            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-text-main">
                    Phases de la compétition
                </h2>
                <button @click="ouvrirModalPhase"
                        :disabled="!equipes?.length"
                        class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-40 disabled:cursor-not-allowed">
                    + Nouvelle Phase
                </button>
            </div>

            <p v-if="!equipes?.length" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                ⚠ Créez d'abord des équipes avant de pouvoir créer des phases.
            </p>

            <!-- Liste des phases -->
            <div v-if="phasesActives.length" class="space-y-4">
                <div v-for="phase in phasesActives" :key="phase.id"
                     class="rounded-xl bg-card shadow-card overflow-hidden">

                    <!-- Header phase -->
                    <div class="border-b border-border-soft bg-page-bg/50 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-bold text-violet-700">
                                        Phase {{ phase.ordre }}
                                    </span>
                                    <h3 class="font-display text-lg font-extrabold text-text-main">
                                        {{ phase.nom }}
                                    </h3>
                                </div>
                                <p v-if="phase.date_debut" class="mt-1 text-xs text-text-sub">
                                    Du {{ formaterDate(phase.date_debut) }}
                                    <span v-if="phase.date_fin"> au {{ formaterDate(phase.date_fin) }}</span>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button @click="ouvrirModalRencontre(phase)"
                                        class="rounded-lg bg-moov-blue px-3 py-1.5 text-xs font-bold text-white transition hover:bg-moov-blue-dark">
                                    + Rencontre
                                </button>
                                <button @click="supprimerPhase(phase)"
                                        class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des rencontres -->
                    <div v-if="phase.rencontres?.length" class="divide-y divide-border-soft">
                        <div v-for="r in phase.rencontres" :key="r.id"
                             class="flex flex-wrap items-center gap-4 p-4 transition hover:bg-page-bg/30">

                            <!-- Date -->
                            <div class="text-center">
                                <p class="font-display text-sm font-extrabold text-text-main">
                                    {{ formaterDate(r.date_match) }}
                                </p>
                                <p class="text-xs text-text-muted">
                                    {{ formaterHeure(r.date_match) }}
                                </p>
                            </div>

                            <!-- Match -->
                            <div class="flex flex-1 items-center justify-center gap-3">
                                <div :class="['flex-1 text-right',
                                    r.vainqueur?.id === r.equipe_a?.id ? 'font-extrabold text-emerald-700' : 'text-text-main']">
                                    {{ r.equipe_a?.nom ?? '—' }}
                                </div>

                                <div class="flex items-center gap-2 rounded-lg bg-page-bg px-3 py-1.5">
                                    <span :class="['font-display text-lg font-extrabold',
                                        r.statut === 'terminee' ? 'text-text-main' : 'text-text-muted']">
                                        {{ r.statut === 'terminee' ? r.score_equipe_a : '−' }}
                                    </span>
                                    <span class="text-text-muted">·</span>
                                    <span :class="['font-display text-lg font-extrabold',
                                        r.statut === 'terminee' ? 'text-text-main' : 'text-text-muted']">
                                        {{ r.statut === 'terminee' ? r.score_equipe_b : '−' }}
                                    </span>
                                </div>

                                <div :class="['flex-1',
                                    r.vainqueur?.id === r.equipe_b?.id ? 'font-extrabold text-emerald-700' : 'text-text-main']">
                                    {{ r.equipe_b?.nom ?? '—' }}
                                </div>
                            </div>

                            <!-- Statut -->
                            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-bold',
                                couleurStatutRencontre(r.statut).bg, couleurStatutRencontre(r.statut).text]">
                                {{ couleurStatutRencontre(r.statut).label }}
                            </span>

                            <!-- Action -->
                            <button @click="ouvrirModalScore(r)"
                                    class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                                {{ r.statut === 'terminee' ? 'Modifier score' : 'Saisir score' }}
                            </button>
                        </div>
                    </div>

                    <!-- Pas de rencontres -->
                    <div v-else class="p-8 text-center">
                        <p class="text-sm text-text-sub">Aucune rencontre planifiée pour cette phase</p>
                    </div>

                    <!-- Génération de la phase suivante -->
                    <div v-if="phaseComplete(phase)" class="border-t border-border-soft p-4">
                        <p v-if="phaseAvecNul(phase)"
                           class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                            ⚠ Certains matchs sont terminés sur un score nul. Départagez-les (prolongations / tirs au but) avant de générer la phase suivante.
                        </p>
                        <p v-else-if="pairesSuivantes(phase).length === 0"
                           class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-700">
                            🏆 {{ gagnantsPhase(phase)[0]?.nom }} remporte la compétition !
                        </p>
                        <button v-else @click="ouvrirModalGeneration(phase)"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                            🏆 Générer la phase suivante
                            ({{ pairesSuivantes(phase).length }} rencontre{{ pairesSuivantes(phase).length > 1 ? 's' : '' }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vide -->
            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucune phase créée</p>
                <p class="mt-1 text-sm text-text-sub">Créez votre première phase de compétition</p>
            </div>
        </div>

        <div v-show="ongletActif === 'classements'" class="space-y-6">

            <div v-if="phasesActives.length">
                <div v-for="phase in phasesActives" :key="phase.id" class="mb-6 rounded-xl bg-card shadow-card overflow-hidden">
                    <div class="border-b border-border-soft bg-page-bg/50 p-5">
                        <h3 class="font-display text-lg font-extrabold text-text-main">
                            Classement — {{ phase.nom }}
                        </h3>
                    </div>

                    <table v-if="classements?.[phase.id]?.length" class="min-w-full divide-y divide-border-soft text-sm">
                        <thead class="bg-page-bg/30">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">#</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Équipe</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">PTS</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">J</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">V</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">N</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">D</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">BP</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">BC</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">Diff</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft bg-card">
                            <tr v-for="c in classements[phase.id]" :key="c.id"
                                :class="['transition hover:bg-page-bg/30',
                                    c.rang === 1 ? 'bg-amber-50/40' : '']">
                                <td class="px-4 py-3">
                                    <span :class="['flex h-7 w-7 items-center justify-center rounded-full font-display text-sm font-extrabold',
                                        c.rang === 1 ? 'bg-amber-100 text-amber-700' :
                                        c.rang === 2 ? 'bg-slate-100 text-slate-700' :
                                        c.rang === 3 ? 'bg-orange-100 text-orange-700' :
                                        'bg-page-bg text-text-sub']">
                                        {{ c.rang }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-bold text-text-main">{{ c.equipe?.nom }}</td>
                                <td class="px-4 py-3 text-center font-display text-lg font-extrabold text-moov-blue">
                                    {{ c.points }}
                                </td>
                                <td class="px-4 py-3 text-center text-text-sub">{{ c.matchs_joues }}</td>
                                <td class="px-4 py-3 text-center font-bold text-emerald-600">{{ c.victoires }}</td>
                                <td class="px-4 py-3 text-center text-text-sub">{{ c.nuls }}</td>
                                <td class="px-4 py-3 text-center font-bold text-red-600">{{ c.defaites }}</td>
                                <td class="px-4 py-3 text-center text-text-main">{{ c.buts_marques }}</td>
                                <td class="px-4 py-3 text-center text-text-main">{{ c.buts_encaisses }}</td>
                                <td class="px-4 py-3 text-center font-bold"
                                    :class="c.difference_buts > 0 ? 'text-emerald-600' : c.difference_buts < 0 ? 'text-red-600' : 'text-text-sub'">
                                    {{ c.difference_buts > 0 ? '+' : '' }}{{ c.difference_buts }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="p-8 text-center">
                        <p class="text-sm text-text-sub">Aucun classement disponible. Saisissez les scores des rencontres.</p>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucune phase créée</p>
                <p class="mt-1 text-sm text-text-sub">Le classement apparaîtra après la création de phases</p>
            </div>
        </div>

        <!-- Modale CRÉATION ÉQUIPE -->
        <div v-if="modalEquipeOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalEquipeOuvert = false">
            <div class="w-full max-w-md rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouvelle équipe</h3>
                </div>
                <form @submit.prevent="creerEquipe" class="space-y-4 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom de l'équipe *</label>
                        <input v-model="formEquipe.nom" type="text" required
                               placeholder="Les Lions de Ouaga"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        <p v-if="formEquipe.errors.nom" class="mt-1 text-xs text-red-600">{{ formEquipe.errors.nom }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Capitaine</label>
                        <input v-model="formEquipe.capitaine" type="text"
                               placeholder="Nom du capitaine"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Catégorie</label>
                        <select v-model="formEquipe.categorie"
                                class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10">
                            <option value="">—</option>
                            <option value="junior">Junior</option>
                            <option value="senior">Senior</option>
                            <option value="mixte">Mixte</option>
                            <option value="feminin">Féminin</option>
                        </select>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalEquipeOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerEquipe" :disabled="formEquipe.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Créer l'équipe
                    </button>
                </div>
            </div>
        </div>

        <!-- Modale CRÉATION PHASE -->
        <div v-if="modalPhaseOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalPhaseOuvert = false">
            <div class="w-full max-w-md rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouvelle phase</h3>
                </div>
                <form @submit.prevent="creerPhase" class="space-y-4 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom de la phase *</label>
                        <input v-model="formPhase.nom" type="text" required
                               placeholder="Phase de groupes, Quarts, Demi, Finale..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Ordre *</label>
                        <input v-model.number="formPhase.ordre" type="number" min="1" required
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Date début</label>
                            <input v-model="formPhase.date_debut" type="date"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Date fin</label>
                            <input v-model="formPhase.date_fin" type="date"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalPhaseOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerPhase" :disabled="formPhase.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Créer la phase
                    </button>
                </div>
            </div>
        </div>

       
        <div v-if="modalRencontreOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalRencontreOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Nouvelle rencontre
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">Phase : <span class="font-bold">{{ phaseRencontre?.nom }}</span></p>
                </div>
                <form @submit.prevent="creerRencontre" class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Équipe A *</label>
                            <select v-model="formRencontre.equipe_a_id" required
                                    class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10">
                                <option value="">Sélectionner</option>
                                <option v-for="e in equipes" :key="e.id" :value="e.id">{{ e.nom }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Équipe B *</label>
                            <select v-model="formRencontre.equipe_b_id" required
                                    class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10">
                                <option value="">Sélectionner</option>
                                <option v-for="e in equipes" :key="e.id" :value="e.id">{{ e.nom }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Date et heure *</label>
                        <input v-model="formRencontre.date_match" type="datetime-local" required
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Lieu</label>
                        <input v-model="formRencontre.lieu_match" type="text"
                               placeholder="Stade municipal, Terrain A..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Arbitre</label>
                        <input v-model="formRencontre.arbitre" type="text"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRencontreOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerRencontre" :disabled="formRencontre.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Planifier
                    </button>
                </div>
            </div>
        </div>

       
        <div v-if="rencontreScore"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="rencontreScore = null">
            <div class="w-full max-w-md rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Saisir le score</h3>
                </div>
                <form @submit.prevent="enregistrerScore" class="space-y-4 p-5">
                    <div class="grid grid-cols-[1fr,auto,1fr] items-center gap-3">
                        <div class="text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-sub">{{ rencontreScore.equipe_a?.nom }}</p>
                            <input v-model.number="formScore.score_equipe_a" type="number" min="0" required
                                   class="mt-2 w-full rounded-lg border-2 border-border-soft px-3 py-3 text-center font-display text-3xl font-extrabold outline-none focus:border-moov-blue"/>
                        </div>
                        <div class="text-2xl font-extrabold text-text-muted">·</div>
                        <div class="text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-sub">{{ rencontreScore.equipe_b?.nom }}</p>
                            <input v-model.number="formScore.score_equipe_b" type="number" min="0" required
                                   class="mt-2 w-full rounded-lg border-2 border-border-soft px-3 py-3 text-center font-display text-3xl font-extrabold outline-none focus:border-moov-blue"/>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Observations</label>
                        <textarea v-model="formScore.observations" rows="2"
                                  placeholder="Notes sur le match..."
                                  class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="rencontreScore = null"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="enregistrerScore" :disabled="formScore.processing"
                            class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>

        <!-- Modale GÉNÉRATION DE LA PHASE SUIVANTE -->
        <div v-if="modalGenerationOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalGenerationOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Générer la phase suivante</h3>
                    <p class="mt-1 text-sm text-text-sub">
                        À partir des vainqueurs de : <span class="font-bold">{{ phaseGeneration?.nom }}</span>
                    </p>
                </div>
                <form @submit.prevent="genererPhaseSuivante" class="space-y-4 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom de la nouvelle phase *</label>
                        <input v-model="formGeneration.nom" type="text" required
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        <p v-if="formGeneration.errors.nom" class="mt-1 text-xs text-red-600">{{ formGeneration.errors.nom }}</p>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Rencontres à planifier</p>
                        <div v-for="(paire, i) in pairesGeneration" :key="i"
                             class="rounded-lg border border-border-soft p-3">
                            <p class="text-sm font-bold text-text-main">
                                {{ paire[0]?.nom }} <span class="text-text-muted">vs</span> {{ paire[1]?.nom }}
                            </p>
                            <input v-model="formGeneration.dates[i]" type="datetime-local" required
                                   class="mt-2 w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <p v-if="formGeneration.errors.dates" class="text-xs text-red-600">{{ formGeneration.errors.dates }}</p>
                    </div>

                    <p v-if="equipeQualifieeSansAdversaire(phaseGeneration)"
                       class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                        ⚠ {{ equipeQualifieeSansAdversaire(phaseGeneration).nom }} est qualifiée directement (nombre impair d'équipes) — sa rencontre suivante devra être créée manuellement le moment venu.
                    </p>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Lieu (optionnel, appliqué à toutes les rencontres)</label>
                        <input v-model="formGeneration.lieu_match" type="text"
                               placeholder="Stade municipal, Terrain A..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>

                    <p v-if="formGeneration.errors.generation" class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                        {{ formGeneration.errors.generation }}
                    </p>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalGenerationOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="genererPhaseSuivante" :disabled="formGeneration.processing"
                            class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        Générer
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>