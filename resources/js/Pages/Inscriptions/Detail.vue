<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    inscription: Object,
})

// ── HELPERS VISUELS ───────────────────────
const couleurStatut = (statut) => ({
    en_attente:  { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-500',    label: 'En attente d\'analyse' },
    en_analyse:  { bg: 'bg-blue-50',     text: 'text-blue-700',     dot: 'bg-blue-500',     label: 'En cours d\'analyse' },
    acceptee:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  dot: 'bg-emerald-500',  label: 'Acceptée' },
    confirmee:   { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Confirmée' },
    refusee:     { bg: 'bg-red-50',      text: 'text-red-700',      dot: 'bg-red-500',      label: 'Refusée' },
    annulee:     { bg: 'bg-slate-100',   text: 'text-slate-600',    dot: 'bg-slate-400',    label: 'Annulée' },
    present:     { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Présent' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const initiales = computed(() => {
    const u = props.inscription.user
    return `${u?.prenom?.[0] ?? ''}${u?.nom?.[0] ?? ''}`.toUpperCase()
})

const typeCode = computed(() => props.inscription.evenement?.type?.code)
const dossier  = computed(() => props.inscription.dossier ?? {})
const peutAgir = computed(() => ['en_attente', 'en_analyse'].includes(props.inscription.statut))

// ── ACTIONS ───────────────────────────────
const analyser = () => {
    if (confirm('Marquer ce dossier comme "en cours d\'analyse" ?')) {
        router.post(`/inscriptions/${props.inscription.id}/analyser`)
    }
}

const accepter = () => {
    if (confirm('Accepter ce dossier ?')) {
        router.post(`/inscriptions/${props.inscription.id}/accepter`)
    }
}

// ── MODALE REFUS ──────────────────────────
const modalRefusOuvert = ref(false)
const motifRefus = ref('')
const erreurMotif = ref('')

const ouvrirModalRefus = () => {
    motifRefus.value = ''
    erreurMotif.value = ''
    modalRefusOuvert.value = true
}

const confirmerRefus = () => {
    if (motifRefus.value.trim().length < 10) {
        erreurMotif.value = 'Le motif doit comporter au moins 10 caractères.'
        return
    }
    router.post(`/inscriptions/${props.inscription.id}/refuser`, {
        motif_refus: motifRefus.value,
    }, {
        onSuccess: () => modalRefusOuvert.value = false,
    })
}

// ── LIBELLÉS DES NIVEAUX/TYPES ────────────
const labelNiveauFormation = (n) => ({
    debutant: 'Débutant', intermediaire: 'Intermédiaire', avance: 'Avancé',
}[n] || n)

const labelVisiteSalon = (v) => ({
    visiteur: 'Visiteur', partenaire_potentiel: 'Partenaire potentiel', client_potentiel: 'Client potentiel',
}[v] || v)

const labelCategorieEquipe = (c) => ({
    junior: 'Junior', senior: 'Senior', mixte: 'Mixte', feminin: 'Féminin',
}[c] || c)
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6">
            <Link href="/inscriptions"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-text-sub hover:text-moov-blue">
                ← Retour à la liste des dossiers
            </Link>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-text-main">
                        Dossier {{ inscription.qr_code }}
                    </h1>
                    <p class="mt-1 text-sm text-text-sub">
                        Soumis le {{ formaterDate(inscription.created_at) }}
                    </p>
                </div>

                <!-- Boutons d'action -->
                <div v-if="peutAgir" class="flex flex-wrap gap-2">
                    <button v-if="inscription.statut === 'en_attente'"
                            @click="analyser"
                            class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Marquer en analyse
                    </button>

                    <button @click="accepter"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Accepter le dossier
                    </button>

                    <button @click="ouvrirModalRefus"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-red-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Refuser
                    </button>
                </div>
            </div>
        </div>

        <!-- ── CONTENU PRINCIPAL ── -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- ──────────── Colonne principale ──────────── -->
            <div class="space-y-6 lg:col-span-2">

                <!-- Carte CANDIDAT -->
                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">
                            Candidat
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-lg font-extrabold text-white">
                                {{ initiales }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-xl font-extrabold text-text-main">
                                    {{ inscription.user?.prenom }} {{ inscription.user?.nom }}
                                </h3>
                                <div class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                    <a :href="`mailto:${inscription.user?.email}`"
                                       class="flex items-center gap-2 text-text-sub hover:text-moov-blue">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ inscription.user?.email }}
                                    </a>
                                    <a v-if="inscription.user?.telephone"
                                       :href="`tel:${inscription.user.telephone}`"
                                       class="flex items-center gap-2 text-text-sub hover:text-moov-blue">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ inscription.user.telephone }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Infos pro communes -->
                        <div v-if="dossier?.organisation || dossier?.fonction"
                             class="mt-5 grid grid-cols-1 gap-4 border-t border-border-soft pt-5 sm:grid-cols-2">
                            <div v-if="dossier?.organisation">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    Organisation
                                </p>
                                <p class="mt-1 font-bold text-text-main">{{ dossier.organisation }}</p>
                            </div>
                            <div v-if="dossier?.fonction">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    Fonction
                                </p>
                                <p class="mt-1 font-bold text-text-main">{{ dossier.fonction }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte ÉVÉNEMENT -->
                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">
                            Événement concerné
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <Link :href="`/evenements/${inscription.evenement?.id}`"
                                      class="font-display text-lg font-extrabold text-text-main hover:text-moov-blue">
                                    {{ inscription.evenement?.titre }}
                                </Link>
                                <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Type</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.evenement?.type?.nom ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Date</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ formaterDate(inscription.evenement?.date_debut) }}</p>
                                    </div>
                                    <div v-if="inscription.evenement?.lieu">
                                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Lieu</p>
                                        <p class="mt-0.5 font-bold text-text-main">{{ inscription.evenement.lieu.nom }}</p>
                                    </div>
                                    <div v-if="inscription.tarif">
                                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Tarif</p>
                                        <p class="mt-0.5 font-bold text-text-main">
                                            {{ inscription.tarif.libelle }} ·
                                            <span v-if="inscription.tarif.montant > 0">
                                                {{ Number(inscription.tarif.montant).toLocaleString('fr-FR') }} {{ inscription.tarif.devise }}
                                            </span>
                                            <span v-else class="text-emerald-600">Gratuit</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte MOTIVATION -->
                <div v-if="dossier?.motivation" class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">
                            Motivation
                        </h2>
                    </div>
                    <div class="p-5">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-text-sub">
                            {{ dossier.motivation }}
                        </p>
                    </div>
                </div>

                <!-- ════════════════════════════════════════ -->
                <!--   CHAMPS SPÉCIFIQUES SELON LE TYPE      -->
                <!-- ════════════════════════════════════════ -->

                <!-- BARA MOUSSO -->
                <div v-if="typeCode === 'BARA_MOUSSO' && (dossier?.nom_association || dossier?.description_projet)"
                     class="rounded-xl border-l-4 border-amber-500 bg-amber-50/30 shadow-card">
                    <div class="border-b border-amber-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">
                            Association et projet
                        </h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div v-if="dossier.nom_association">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Nom de l'association</p>
                            <p class="mt-1 font-bold text-text-main">{{ dossier.nom_association }}</p>
                        </div>
                        <div v-if="dossier.description_projet">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Description du projet</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-text-sub">{{ dossier.description_projet }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-if="dossier.nb_membres_association">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Membres</p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-amber-700">
                                    {{ dossier.nb_membres_association }}
                                </p>
                            </div>
                            <div v-if="dossier.budget_projet">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Budget projet</p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-amber-700">
                                    {{ Number(dossier.budget_projet).toLocaleString('fr-FR') }} <span class="text-base">FCFA</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SPORT -->
                <div v-if="typeCode === 'SPORT' && dossier?.nom_equipe"
                     class="rounded-xl border-l-4 border-blue-500 bg-blue-50/30 shadow-card">
                    <div class="border-b border-blue-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Équipe sportive</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Nom de l'équipe</p>
                            <p class="mt-1 font-display text-xl font-extrabold text-blue-700">{{ dossier.nom_equipe }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div v-if="dossier.nb_joueurs">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Joueurs</p>
                                <p class="mt-1 font-display text-xl font-extrabold text-text-main">{{ dossier.nb_joueurs }}</p>
                            </div>
                            <div v-if="dossier.categorie_equipe">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Catégorie</p>
                                <p class="mt-1 font-bold text-text-main">{{ labelCategorieEquipe(dossier.categorie_equipe) }}</p>
                            </div>
                            <div v-if="dossier.responsable_equipe">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Responsable</p>
                                <p class="mt-1 font-bold text-text-main">{{ dossier.responsable_equipe }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HACKATHON -->
                <div v-if="typeCode === 'HACK' && dossier?.nom_equipe_hack"
                     class="rounded-xl border-l-4 border-orange-500 bg-orange-50/30 shadow-card">
                    <div class="border-b border-orange-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Équipe Hackathon</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Nom de l'équipe</p>
                                <p class="mt-1 font-bold text-text-main">{{ dossier.nom_equipe_hack }}</p>
                            </div>
                            <div v-if="dossier.nb_membres_equipe">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Membres</p>
                                <p class="mt-1 font-display text-xl font-extrabold text-orange-700">{{ dossier.nb_membres_equipe }}</p>
                            </div>
                        </div>
                        <div v-if="dossier.competences_techniques">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Compétences techniques</p>
                            <p class="mt-1 text-sm text-text-sub">{{ dossier.competences_techniques }}</p>
                        </div>
                        <div v-if="dossier.stack_technologique">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Stack technologique</p>
                            <p class="mt-1 font-mono text-sm text-text-main">{{ dossier.stack_technologique }}</p>
                        </div>
                    </div>
                </div>

                <!-- FORMATION -->
                <div v-if="typeCode === 'FORMATION' && (dossier?.niveau_formation || dossier?.objectifs_apprentissage)"
                     class="rounded-xl border-l-4 border-emerald-500 bg-emerald-50/30 shadow-card">
                    <div class="border-b border-emerald-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Profil & objectifs</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div v-if="dossier.niveau_formation">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Niveau actuel</p>
                            <p class="mt-1 inline-block rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700">
                                {{ labelNiveauFormation(dossier.niveau_formation) }}
                            </p>
                        </div>
                        <div v-if="dossier.objectifs_apprentissage">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Objectifs d'apprentissage</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-text-sub">{{ dossier.objectifs_apprentissage }}</p>
                        </div>
                    </div>
                </div>

                <!-- SALON -->
                <div v-if="typeCode === 'SALON' && dossier?.secteur_activite"
                     class="rounded-xl border-l-4 border-indigo-500 bg-indigo-50/30 shadow-card">
                    <div class="border-b border-indigo-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Profil professionnel</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Secteur d'activité</p>
                                <p class="mt-1 font-bold text-text-main">{{ dossier.secteur_activite }}</p>
                            </div>
                            <div v-if="dossier.type_visite_salon">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Type de visite</p>
                                <p class="mt-1 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                                    {{ labelVisiteSalon(dossier.type_visite_salon) }}
                                </p>
                            </div>
                        </div>
                        <div v-if="dossier.interets_b2b">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Intérêts B2B</p>
                            <p class="mt-1 text-sm text-text-sub">{{ dossier.interets_b2b }}</p>
                        </div>
                    </div>
                </div>

                <!-- CHALLENGE -->
                <div v-if="typeCode === 'CHALLENGE' && dossier?.titre_idee"
                     class="rounded-xl border-l-4 border-violet-500 bg-violet-50/30 shadow-card">
                    <div class="border-b border-violet-200 p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Idée innovante</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Titre de l'idée</p>
                            <p class="mt-1 font-display text-lg font-extrabold text-violet-700">{{ dossier.titre_idee }}</p>
                        </div>
                        <div v-if="dossier.secteur_idee">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Secteur</p>
                            <p class="mt-1 inline-block rounded-full bg-violet-100 px-3 py-1 text-sm font-bold text-violet-700 capitalize">
                                {{ dossier.secteur_idee }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DOCUMENT JOINT -->
                <div v-if="dossier?.fichier_joint_url" class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Document joint</h2>
                    </div>
                    <div class="p-5">
                        <a :href="dossier.fichier_joint_url"
                           target="_blank"
                           download
                           class="inline-flex items-center gap-3 rounded-lg border border-border-soft bg-page-bg/50 p-4 transition hover:border-moov-blue hover:bg-moov-blue-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-100">
                                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-text-main">Document joint</p>
                                <p class="text-xs text-text-sub">Cliquer pour télécharger</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ──────────── Colonne latérale ──────────── -->
            <aside class="space-y-6">

                <!-- Statut actuel -->
                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-base font-bold text-text-main">Statut</h3>
                    </div>
                    <div class="p-5">
                        <div :class="['flex items-center gap-2 rounded-lg p-3',
                            couleurStatut(inscription.statut).bg]">
                            <span :class="['h-2.5 w-2.5 rounded-full', couleurStatut(inscription.statut).dot]"/>
                            <span :class="['font-bold uppercase tracking-wider text-sm', couleurStatut(inscription.statut).text]">
                                {{ couleurStatut(inscription.statut).label }}
                            </span>
                        </div>

                        <!-- Motif refus -->
                        <div v-if="inscription.statut === 'refusee' && inscription.motif_refus"
                             class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-red-700">Motif de refus</p>
                            <p class="mt-1 text-sm text-red-900">{{ inscription.motif_refus }}</p>
                        </div>

                        <!-- Analysé par -->
                        <div v-if="inscription.analyse_par" class="mt-4 border-t border-border-soft pt-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Analysé par</p>
                            <p class="mt-1 font-bold text-text-main">
                                {{ inscription.analyse_par.prenom }} {{ inscription.analyse_par.nom }}
                            </p>
                            <p v-if="inscription.date_analyse" class="text-xs text-text-sub">
                                Le {{ formaterDate(inscription.date_analyse) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Historique -->
                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-base font-bold text-text-main">Historique</h3>
                    </div>
                    <div class="p-5">
                        <ol class="relative space-y-4 border-l-2 border-border-soft pl-4">
                            <li class="relative">
                                <span class="absolute -left-[1.4rem] top-1 flex h-3 w-3 rounded-full bg-amber-500 ring-2 ring-white"/>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Soumission</p>
                                <p class="text-sm font-bold text-text-main">{{ formaterDate(inscription.created_at) }}</p>
                            </li>
                            <li v-if="inscription.date_analyse" class="relative">
                                <span :class="['absolute -left-[1.4rem] top-1 flex h-3 w-3 rounded-full ring-2 ring-white',
                                    inscription.statut === 'refusee' ? 'bg-red-500'
                                    : inscription.statut === 'en_analyse' ? 'bg-blue-500'
                                    : 'bg-emerald-500']"/>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    {{ inscription.statut === 'refusee' ? 'Refus' :
                                       inscription.statut === 'en_analyse' ? 'Mise en analyse' :
                                       'Décision' }}
                                </p>
                                <p class="text-sm font-bold text-text-main">{{ formaterDate(inscription.date_analyse) }}</p>
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Référence -->
                <div class="rounded-xl bg-page-bg p-4 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Référence du dossier</p>
                    <p class="mt-2 font-mono text-sm font-bold text-text-main">{{ inscription.qr_code }}</p>
                </div>
            </aside>
        </div>

        <!-- ════════════════════════ -->
        <!--   MODALE DE REFUS         -->
        <!-- ════════════════════════ -->
        <div v-if="modalRefusOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalRefusOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Refuser ce dossier
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Candidat : <span class="font-bold">{{ inscription.user?.prenom }} {{ inscription.user?.nom }}</span>
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Motif du refus *
                        <span class="text-text-muted normal-case">(min. 10 caractères, communiqué au candidat)</span>
                    </label>
                    <textarea v-model="motifRefus" rows="4"
                              placeholder="Expliquez pourquoi le dossier n'est pas retenu..."
                              class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/10"/>
                    <p v-if="erreurMotif" class="mt-1 text-xs text-red-600">{{ erreurMotif }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRefusOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRefus"
                            class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700">
                        Confirmer le refus
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>