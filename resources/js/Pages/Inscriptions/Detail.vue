<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    inscription: { type: Object, required: true },
    userRole: { type: Object, required: true },
})

// Helpers
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const couleurStatut = (statut) => ({
    preinscrit: { bg: 'bg-amber-100', text: 'text-amber-700' },
    preselectionne: { bg: 'bg-blue-100', text: 'text-blue-700' },
    dossier_soumis: { bg: 'bg-indigo-100', text: 'text-indigo-700' },
    en_analyse: { bg: 'bg-violet-100', text: 'text-violet-700' },
    recommandee: { bg: 'bg-cyan-100', text: 'text-cyan-700' },
    acceptee: { bg: 'bg-emerald-100', text: 'text-emerald-700' },
    confirmee: { bg: 'bg-emerald-200', text: 'text-emerald-900' },
    refusee: { bg: 'bg-red-100', text: 'text-red-700' },
    present: { bg: 'bg-emerald-200', text: 'text-emerald-900' },
    annulee: { bg: 'bg-slate-100', text: 'text-slate-600' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600' })

const labelStatut = (statut) => ({
    preinscrit: 'Pré-inscrit', preselectionne: 'Présélectionné',
    dossier_soumis: 'Dossier soumis', en_analyse: 'En analyse',
    recommandee: 'Recommandée', acceptee: 'Acceptée',
    confirmee: 'Confirmée', refusee: 'Refusée',
    present: 'Présent', annulee: 'Annulée',
}[statut] || statut)

// ──── ACTIONS WORKFLOW ────
const typeCode = computed(() => props.inscription.evenement?.type_evenement?.code)
const necessitePreselection = computed(() => !['CONF', 'FORMATION'].includes(typeCode.value))

// Présélection (Niveau 1 → Niveau 2)
const peutPreselectionner = computed(() =>
    props.inscription.statut === 'preinscrit' && necessitePreselection.value
)

const presele = () => {
    if (confirm('Présélectionner ce candidat et lui envoyer le lien du dossier complet ?')) {
        router.post(`/inscriptions/${props.inscription.id}/preselectionner`, {}, {
            preserveScroll: true,
        })
    }
}

// Recommandation (Organisateur)
const peutRecommander = computed(() =>
    ['dossier_soumis', 'en_analyse'].includes(props.inscription.statut)
)
const modalRecommandation = ref(false)
const noteOrganisateur = ref('')

const recommander = () => {
    router.post(`/inscriptions/${props.inscription.id}/recommander`, {
        note_organisateur: noteOrganisateur.value,
    }, {
        onSuccess: () => modalRecommandation.value = false,
        preserveScroll: true,
    })
}

// Validation finale (Responsable)
const peutValider = computed(() => {
    if (!props.userRole.estResponsable) return false
    if (!necessitePreselection.value) return props.inscription.statut === 'preinscrit'
    return props.inscription.statut === 'recommandee'
})

const valider = () => {
    if (confirm('Valider définitivement cette inscription ? Un email avec le QR code sera envoyé.')) {
        router.post(`/inscriptions/${props.inscription.id}/valider`, {}, {
            preserveScroll: true,
        })
    }
}

// Refus
const peutRefuser = computed(() =>
    ['preinscrit', 'preselectionne', 'dossier_soumis', 'en_analyse', 'recommandee']
        .includes(props.inscription.statut)
)
const modalRefus = ref(false)
const motifRefus = ref('')

const ouvrirModalRefus = () => {
    motifRefus.value = ''
    modalRefus.value = true
}

const confirmerRefus = () => {
    if (motifRefus.value.length < 10) {
        alert('Le motif doit contenir au moins 10 caractères')
        return
    }
    router.post(`/inscriptions/${props.inscription.id}/refuser`, {
        motif_refus: motifRefus.value,
    }, {
        onSuccess: () => modalRefus.value = false,
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

        <!-- Retour -->
        <Link href="/inscriptions"
            class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Toutes les inscriptions
        </Link>

        <!-- En-tête -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Dossier d'inscription
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main">
                    {{ inscription.user?.prenom }} {{ inscription.user?.nom }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ inscription.user?.email }}
                    <span v-if="inscription.user?.telephone"> · {{ inscription.user.telephone }}</span>
                </p>
            </div>

            <span :class="['rounded-full px-4 py-1.5 text-sm font-bold',
                couleurStatut(inscription.statut).bg, couleurStatut(inscription.statut).text]">
                {{ labelStatut(inscription.statut) }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- COLONNE PRINCIPALE -->
            <div class="space-y-6 lg:col-span-2">

                <!-- Infos événement -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Événement
                        </p>
                        <h2 class="mt-1 font-display text-xl font-extrabold text-text-main">
                            {{ inscription.evenement?.titre }}
                        </h2>
                        <p class="mt-1 text-sm text-text-sub">
                            {{ inscription.evenement?.type_evenement?.nom }} · {{
                                formaterDate(inscription.evenement?.date_debut) }}
                        </p>
                    </div>
                </div>

                <!-- Motivation -->
                <div v-if="inscription.motivation" class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Motivation du candidat
                        </h3>
                    </div>
                    <div class="p-5">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-text-main">
                            {{ inscription.motivation }}
                        </p>
                    </div>
                </div>

               <!-- ═══ DOSSIER DÉPOSÉ PAR LE PARTICIPANT ═══ -->
                <div v-if="inscription.dossier" class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Dossier de candidature
                        </h3>
                        <p class="mt-1 text-xs text-text-muted">
                            Informations déposées par le participant
                        </p>
                    </div>

                    <div class="space-y-4 p-5 text-sm">

                        <!-- ─── INFOS COMMUNES ─── -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div v-if="inscription.dossier.organisation">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Organisation</p>
                                <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.organisation }}</p>
                            </div>
                            <div v-if="inscription.dossier.fonction">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Fonction</p>
                                <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.fonction }}</p>
                            </div>
                        </div>

                        <div v-if="inscription.dossier.motivation" class="rounded-lg bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Motivation</p>
                            <p class="mt-1 whitespace-pre-line leading-relaxed text-text-main">
                                {{ inscription.dossier.motivation }}
                            </p>
                        </div>

                        <!-- ─── DOCUMENT JOINT ─── -->
                        <div v-if="inscription.dossier.fichier_joint" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-blue-900">Document joint</p>
                                <p class="text-[11px] text-blue-700 truncate">{{ inscription.dossier.fichier_joint.split('/').pop() }}</p>
                            </div>
                            <a :href="`/storage/${inscription.dossier.fichier_joint}`" target="_blank" download
                               class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-blue-700">
                                Télécharger
                            </a>
                        </div>

                        <!-- ─── BARA MOUSSO ─── -->
                        <template v-if="typeCode === 'BARA_MOUSSO'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-rose-600">Projet associatif</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-if="inscription.dossier.nom_association">
                                        <p class="text-[11px] font-bold text-text-muted">Nom association</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nom_association }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.nb_membres_association">
                                        <p class="text-[11px] font-bold text-text-muted">Membres</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nb_membres_association }} personnes</p>
                                    </div>
                                </div>
                                <div v-if="inscription.dossier.description_projet" class="mt-3">
                                    <p class="text-[11px] font-bold text-text-muted">Description du projet</p>
                                    <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.dossier.description_projet }}</p>
                                </div>
                                <div v-if="inscription.dossier.budget_projet" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Budget</span>
                                    <span class="font-display text-base font-extrabold text-rose-700">
                                        {{ Number(inscription.dossier.budget_projet).toLocaleString('fr-FR') }} FCFA
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- ─── SPORT ─── -->
                        <template v-else-if="typeCode === 'SPORT'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-blue-600">Équipe sportive</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-if="inscription.dossier.nom_equipe">
                                        <p class="text-[11px] font-bold text-text-muted">Nom de l'équipe</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nom_equipe }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.nb_joueurs">
                                        <p class="text-[11px] font-bold text-text-muted">Joueurs</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nb_joueurs }} joueurs</p>
                                    </div>
                                    <div v-if="inscription.dossier.categorie_equipe">
                                        <p class="text-[11px] font-bold text-text-muted">Catégorie</p>
                                        <p class="mt-0.5 font-bold text-text-main capitalize">{{ inscription.dossier.categorie_equipe }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.responsable_equipe">
                                        <p class="text-[11px] font-bold text-text-muted">Responsable</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.responsable_equipe }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ─── HACKATHON ─── -->
                        <template v-else-if="typeCode === 'HACK'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-orange-600">Équipe Hackathon</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-if="inscription.dossier.nom_equipe_hack">
                                        <p class="text-[11px] font-bold text-text-muted">Nom équipe</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nom_equipe_hack }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.nb_membres_equipe">
                                        <p class="text-[11px] font-bold text-text-muted">Membres</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.nb_membres_equipe }} personnes</p>
                                    </div>
                                </div>
                                <div v-if="inscription.dossier.competences_techniques" class="mt-3">
                                    <p class="text-[11px] font-bold text-text-muted">Compétences techniques</p>
                                    <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.dossier.competences_techniques }}</p>
                                </div>
                                <div v-if="inscription.dossier.stack_technologique" class="mt-3">
                                    <p class="text-[11px] font-bold text-text-muted">Stack technologique</p>
                                    <p class="mt-1 text-text-main">{{ inscription.dossier.stack_technologique }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- ─── FORMATION ─── -->
                        <template v-else-if="typeCode === 'FORMATION'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-emerald-600">Profil apprenant</p>
                                <div v-if="inscription.dossier.niveau_formation" class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"/>
                                    <span class="text-xs font-bold capitalize text-emerald-700">Niveau {{ inscription.dossier.niveau_formation }}</span>
                                </div>
                                <div v-if="inscription.dossier.objectifs_apprentissage">
                                    <p class="text-[11px] font-bold text-text-muted">Objectifs d'apprentissage</p>
                                    <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.dossier.objectifs_apprentissage }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- ─── CHALLENGE ─── -->
                        <template v-else-if="typeCode === 'CHALLENGE'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-violet-600">Idée innovante</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-if="inscription.dossier.titre_idee" class="sm:col-span-2">
                                        <p class="text-[11px] font-bold text-text-muted">Titre de l'idée</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.titre_idee }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.secteur_idee">
                                        <p class="text-[11px] font-bold text-text-muted">Secteur</p>
                                        <p class="mt-0.5 font-bold text-text-main capitalize">{{ inscription.dossier.secteur_idee }}</p>
                                    </div>
                                </div>
                                <a v-if="inscription.dossier.fichier_presentation"
                                   :href="`/storage/${inscription.dossier.fichier_presentation}`" target="_blank" download
                                   class="mt-3 inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-violet-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Présentation du projet
                                </a>
                            </div>
                        </template>

                        <!-- ─── SALON ─── -->
                        <template v-else-if="typeCode === 'SALON'">
                            <div class="border-t border-border-soft pt-4">
                                <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-indigo-600">Visite stand</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-if="inscription.dossier.secteur_activite">
                                        <p class="text-[11px] font-bold text-text-muted">Secteur d'activité</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.dossier.secteur_activite }}</p>
                                    </div>
                                    <div v-if="inscription.dossier.type_visite_salon">
                                        <p class="text-[11px] font-bold text-text-muted">Type de visite</p>
                                        <p class="mt-0.5 font-bold text-text-main capitalize">{{ inscription.dossier.type_visite_salon.replace('_', ' ') }}</p>
                                    </div>
                                </div>
                                <div v-if="inscription.dossier.interets_b2b" class="mt-3">
                                    <p class="text-[11px] font-bold text-text-muted">Intérêts B2B</p>
                                    <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.dossier.interets_b2b }}</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ═══ TARIF (si applicable) ═══ -->
                <div v-if="inscription.tarif" class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Tarification
                        </h3>
                    </div>
                    <div class="flex items-center justify-between p-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Formule choisie</p>
                            <p class="mt-1 font-bold text-text-main">{{ inscription.tarif.libelle || inscription.tarif.nom }}</p>
                        </div>
                        <div class="text-right">
                            <p v-if="Number(inscription.tarif.montant) > 0" class="font-display text-2xl font-extrabold text-moov-blue">
                                {{ Number(inscription.tarif.montant).toLocaleString('fr-FR') }}
                                <span class="text-sm">FCFA</span>
                            </p>
                            <p v-else class="font-display text-2xl font-extrabold text-emerald-600">
                                Gratuit
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ ÉTAT VIDE (si pas de dossier déposé) ═══ -->
                <div v-if="!inscription.dossier && inscription.statut === 'preinscrit'"
                     class="rounded-xl border-2 border-dashed border-border-soft bg-page-bg/30 p-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="mt-3 text-sm font-bold text-text-main">Aucun dossier déposé pour le moment</p>
                    <p class="mt-1 text-xs text-text-sub">
                        Le candidat n'a pas encore complété son dossier de candidature.
                    </p>
                </div>

                <!-- Note organisateur (si recommandée) -->
                <div v-if="inscription.note_organisateur" class="rounded-xl border border-cyan-200 bg-cyan-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">
                        Note de l'organisateur
                    </p>
                    <p class="mt-2 text-sm text-cyan-900">{{ inscription.note_organisateur }}</p>
                    <p v-if="inscription.recommande_par_user" class="mt-2 text-xs text-cyan-700">
                        Par {{ inscription.recommande_par_user.prenom }} {{ inscription.recommande_par_user.nom }}
                    </p>
                </div>

                <!-- Motif refus -->
                <div v-if="inscription.statut === 'refusee' && inscription.motif_refus"
                    class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                        Motif du refus
                    </p>
                    <p class="mt-2 text-sm text-red-900">{{ inscription.motif_refus }}</p>
                </div>
            </div>

            <!-- SIDEBAR : ACTIONS -->
            <aside class="space-y-4">

                <!-- ACTIONS WORKFLOW -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Actions
                        </h3>
                    </div>

                    <div class="space-y-2 p-5">

                        <!-- Présélectionner -->
                        <button v-if="peutPreselectionner" @click="presele"
                            class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                            Présélectionner
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Envoyer le lien du dossier complet
                            </span>
                        </button>

                        <!-- Recommander (Organisateur) -->
                        <button v-if="peutRecommander" @click="modalRecommandation = true"
                            class="w-full rounded-lg bg-cyan-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-cyan-700">
                            Recommander
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Avis favorable pour validation finale
                            </span>
                        </button>

                        <!-- Valider (Responsable) -->
                        <button v-if="peutValider" @click="valider"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                             Valider définitivement
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Acceptation finale + QR code
                            </span>
                        </button>

                        <!-- Refuser -->
                        <button v-if="peutRefuser" @click="ouvrirModalRefus"
                            class="w-full rounded-lg border-2 border-red-300 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50">
                             Refuser
                        </button>

                        <!-- Aucune action -->
                        <p v-if="!peutPreselectionner && !peutRecommander && !peutValider && !peutRefuser"
                            class="text-center text-xs text-text-muted py-3">
                            Aucune action disponible
                        </p>
                    </div>
                </div>

                <!-- HISTORIQUE -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Historique
                        </h3>
                    </div>
                    <div class="space-y-3 p-5 text-xs">

                        <div>
                            <p class="font-bold text-text-main">Soumis</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.created_at) }}</p>
                        </div>

                        <div v-if="inscription.presele_le">
                            <p class="font-bold text-text-main">Présélectionné</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.presele_le) }}</p>
                            <p v-if="inscription.presele_par_user" class="text-text-muted">
                                par {{ inscription.presele_par_user.prenom }} {{ inscription.presele_par_user.nom }}
                            </p>
                        </div>

                        <div v-if="inscription.recommande_le">
                            <p class="font-bold text-text-main">Recommandé</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.recommande_le) }}</p>
                            <p v-if="inscription.recommande_par_user" class="text-text-muted">
                                par {{ inscription.recommande_par_user.prenom }} {{ inscription.recommande_par_user.nom
                                }}
                            </p>
                        </div>

                        <div v-if="inscription.valide_le">
                            <p class="font-bold text-emerald-700">Validé</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.valide_le) }}</p>
                            <p v-if="inscription.valide_par_user" class="text-text-muted">
                                par {{ inscription.valide_par_user.prenom }} {{ inscription.valide_par_user.nom }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- QR CODE si confirmé -->
                <div v-if="['confirmee', 'acceptee', 'present'].includes(inscription.statut)"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                        Badge participant
                    </p>
                    <p class="mt-2 font-mono text-xl font-extrabold text-emerald-900 tracking-wider">
                        {{ inscription.qr_code }}
                    </p>
                </div>
            </aside>
        </div>

        <!-- ════════ MODALE RECOMMANDATION ════════ -->
        <div v-if="modalRecommandation" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="modalRecommandation = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Recommander cette candidature
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Votre avis sera pris en compte par le responsable dCIRP pour la validation finale.
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Note ou commentaire (optionnel)
                    </label>
                    <textarea v-model="noteOrganisateur" rows="4" placeholder="Pourquoi recommandez-vous ce candidat ?"
                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRecommandation = false"
                        class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="recommander"
                        class="rounded-lg bg-cyan-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-cyan-700">
                        Recommander
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════ MODALE REFUS ════════ -->
        <div v-if="modalRefus" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="modalRefus = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Refuser cette candidature
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Indiquez le motif - il sera communiqué au candidat par email.
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Motif * (min. 10 caractères)
                    </label>
                    <textarea v-model="motifRefus" rows="4"
                        placeholder="Ex: Le profil ne correspond pas aux critères du concours..."
                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                    <p class="mt-1 text-xs text-text-muted">{{ motifRefus.length }} caractères</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRefus = false"
                        class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRefus" :disabled="motifRefus.length < 10"
                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-50">
                        Refuser et notifier
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>