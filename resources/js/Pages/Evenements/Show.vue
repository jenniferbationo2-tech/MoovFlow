<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    evenement: Object,
    budget: Object,
    peutGererBudget: { type: Boolean, default: false },
    postesBenevoles: { type: Array, default: () => [] },
    dejaInscrit: { type: Boolean, default: false },
    inscriptionId: { type: Number, default: null },
})


const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const userId = computed(() => page.props.auth?.user?.id)
const userRoles = computed(() => page.props.auth?.user?.roles?.map(r => r.name) ?? [])
const roles = computed(() => user.value?.roles ?? [])

const estStaff = computed(() =>
    roles.value.some(r => ['responsable_dcirp', 'organisateur'].includes(r))
)
const Layout = computed(() => estStaff.value ? DashboardLayout : PublicLayout)

// ═══ PERMISSIONS PRÉCISES PAR RÔLE ═══

// Admin : SUPERVISION uniquement (lecture seule)
const estAdmin = computed(() => userRoles.value.includes('admin'))

// Responsable dCIRP : peut valider + créer + modifier ses propres events
const estResponsable = computed(() => userRoles.value.includes('responsable_dcirp'))

// Organisateur (pur, non-admin, non-responsable)
const estOrganisateurPur = computed(() =>
    userRoles.value.includes('organisateur') &&
    !userRoles.value.includes('admin') &&
    !userRoles.value.includes('responsable_dcirp')
)

// Est créateur de cet événement
const estCreateur = computed(() => props.evenement?.created_by === userId.value)

// PEUT MODIFIER : créateur (orga OU responsable qui a créé)
const peutModifier = computed(() =>
    !estAdmin.value && estCreateur.value && (estResponsable.value || estOrganisateurPur.value)
)

// PEUT DEMANDER VALIDATION : organisateur créateur d'un brouillon
const peutDemanderValidation = computed(() =>
    estOrganisateurPur.value &&
    estCreateur.value &&
    props.evenement?.statut === 'brouillon'
)

// PEUT VALIDER : responsable face à un événement en_validation
const peutValider = computed(() =>
    estResponsable.value &&
    ['brouillon', 'en_validation'].includes(props.evenement?.statut) &&
    !estCreateur.value  // Ne valide pas ses propres événements
)

// PEUT REJETER : responsable face à un événement en_validation
const peutRejeter = computed(() =>
    estResponsable.value &&
    props.evenement?.statut === 'en_validation' &&
    !estCreateur.value
)

// PEUT SUPPRIMER : créateur seulement (PAS l'admin)
const peutSupprimer = computed(() =>
    !estAdmin.value && estCreateur.value
)

// Pour rétro-compatibilité (utilisé dans template ancien)
const estOrganisateur = estStaff

const estParticipant = computed(() =>
    userRoles.value.includes('participant') &&
    !userRoles.value.includes('admin') &&
    !userRoles.value.includes('responsable_dcirp')
)

const peutSInscrire = computed(() =>
    !user.value || roles.value.includes('participant') || roles.value.length === 0
)


// ═══ MODALES DE CONFIRMATION ═══
const modalConfirm = ref({
    show: false,
    type: 'default',
    title: '',
    message: '',
    confirmText: 'Confirmer',
    action: null,
})

const ouvrirModalConfirm = (config) => {
    modalConfirm.value = { ...modalConfirm.value, ...config, show: true }
}

const fermerModal = () => {
    modalConfirm.value.show = false
}

const executerAction = () => {
    if (modalConfirm.value.action) {
        modalConfirm.value.action()
    }
    fermerModal()
}

const supprimer = () => {
    ouvrirModalConfirm({
        type: 'danger',
        title: 'Archiver cet événement ?',
        message: `L'événement « ${props.evenement.titre} » sera archivé. Cette action est réversible par un administrateur.`,
        confirmText: 'Oui, archiver',
        action: () => router.delete(`/evenements/${props.evenement.id}`)
    })
}

const valider = () => {
    ouvrirModalConfirm({
        type: 'success',
        title: 'Valider et publier ?',
        message: `L'événement « ${props.evenement.titre} » sera publié immédiatement et visible publiquement.`,
        confirmText: 'Valider et publier',
        action: () => router.post(`/evenements/${props.evenement.id}/valider`)
    })
}

const demanderValidation = () => {
    ouvrirModalConfirm({
        type: 'warning',
        title: 'Demander la validation ?',
        message: 'Une notification sera envoyée au responsable dCIRP. Vous ne pourrez plus modifier l\'événement tant qu\'il n\'est pas validé ou rejeté.',
        confirmText: 'Demander validation',
        action: () => router.post(`/evenements/${props.evenement.id}/demander-validation`)
    })
}

const modalRejetOuvert = ref(false)
const motifRejet = ref(false)
// ═══ MODALE DEMANDER MODIFICATIONS ═══
const modalModifs = ref(false)
const messageModifications = ref('')

const ouvrirModalModifs = () => {
    messageModifications.value = ''
    modalModifs.value = true
}

const fermerModalModifs = () => {
    modalModifs.value = false
}

const envoyerDemandeModifs = () => {
    if (messageModifications.value.length < 10) {
        return
    }
    router.post(`/evenements/${props.evenement.id}/demander-modifications`, {
        modifications_demandees: messageModifications.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            modalModifs.value = false
        },
    })
}

// ═══ PERMISSION : Peut demander des modifications ═══
const peutDemanderModifs = computed(() =>
    estResponsable.value &&
    ['en_validation', 'brouillon'].includes(props.evenement?.statut) &&
    !estCreateur.value
)
const ouvrirModalRejet = () => {
    motifRejet.value = ''
    modalRejetOuvert.value = true
}
const confirmerRejet = () => {
    if (motifRejet.value.trim().length < 10) {
        alert('Le motif doit contenir au moins 10 caractères')
        return
    }
    router.post(`/evenements/${props.evenement.id}/rejeter`, {
        motif_rejet: motifRejet.value,
    }, {
        onSuccess: () => modalRejetOuvert.value = false,
        preserveScroll: true,
    })
}



const modalBenevolatOuvert = ref(false)
const posteSelectionne = ref(null)

const formBenevolat = useForm({
    motivation: '',
    experience: '',
    disponibilites: '',
})

const ouvrirBenevolat = (poste) => {
    posteSelectionne.value = poste
    formBenevolat.reset()
    modalBenevolatOuvert.value = true
}

const candidater = () => {
    formBenevolat.post(`/postes-benevoles/${posteSelectionne.value.id}/candidater`, {
        onSuccess: () => {
            modalBenevolatOuvert.value = false
            posteSelectionne.value = null
        },
        preserveScroll: true,
    })
}

// ─── BUDGET : ligne recette/dépense ───
const ligneForm = useForm({
    libelle: '',
    montant: null,
    type: 'depense',
})

const ajouterLigneBudget = () => {
    ligneForm.post(`/budgets/${props.budget.id}/lignes`, {
        preserveScroll: true,
        onSuccess: () => ligneForm.reset(),
    })
}

const supprimerLigneBudget = (ligne) => {
    router.delete(`/lignes-budget/${ligne.id}`, { preserveScroll: true })
}

const formaterFCFA = (m) => Number(m ?? 0).toLocaleString('fr-FR') + ' FCFA'


const typeCode = computed(() => props.evenement.type_evenement?.code)

const couleurType = (code) => ({
    BARA_MOUSSO: { bg: 'bg-rose-50', text: 'text-rose-700', accent: 'bg-rose-600' },
    CONF: { bg: 'bg-blue-50', text: 'text-blue-700', accent: 'bg-blue-600' },
    SPORT: { bg: 'bg-cyan-50', text: 'text-cyan-700', accent: 'bg-cyan-600' },
    CHALLENGE: { bg: 'bg-violet-50', text: 'text-violet-700', accent: 'bg-violet-600' },
    FORMATION: { bg: 'bg-emerald-50', text: 'text-emerald-700', accent: 'bg-emerald-600' },
    HACK: { bg: 'bg-orange-50', text: 'text-orange-700', accent: 'bg-orange-600' },
    SALON: { bg: 'bg-indigo-50', text: 'text-indigo-700', accent: 'bg-indigo-600' },
}[code] || { bg: 'bg-slate-50', text: 'text-slate-700', accent: 'bg-slate-600' })

const couleurStatut = (statut) => ({
    publie: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publié' },
    en_validation: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'En validation' },
    en_cours: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', label: 'En cours' },
    termine: { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: 'Terminé' },
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    annule: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const labelType = (code) => ({
    BARA_MOUSSO: 'Concours Bara Mousso',
    CONF: 'Conférence',
    SPORT: 'Tournoi sportif',
    CHALLENGE: 'Challenge Innovation',
    FORMATION: 'Formation numérique',
    HACK: 'Hackathon',
    SALON: 'Salon professionnel',
}[code] || 'Événement')

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const statutDynamique = computed(() => {
    const ev = props.evenement
    if (!ev) return 'inconnu'
    if (ev.statut === 'annule') return 'annule'
    if (ev.statut === 'brouillon' || ev.statut === 'en_validation') return ev.statut

    const now = new Date()
    const debut = ev.date_debut ? new Date(ev.date_debut) : null
    const fin = ev.date_fin ? new Date(ev.date_fin) : null

    if (fin && fin < now) return 'termine'
    if (debut && fin && debut <= now && now <= fin) return 'en_cours'
    if (debut && debut > now) return 'a_venir'

    return ev.statut || 'publie'
})

const couleurStatutDyn = computed(() => ({
    a_venir:       { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'À venir' },
    en_cours:      { bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500',    label: 'En cours' },
    termine:       { bg: 'bg-slate-100',  text: 'text-slate-600',   dot: 'bg-slate-400',   label: 'Terminé' },
    annule:        { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     label: 'Annulé' },
    brouillon:     { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   label: 'Brouillon' },
    en_validation: { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   label: 'En validation' },
    publie:        { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publié' },
}[statutDynamique.value] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statutDynamique.value }))

// ═══ Visibilité des infos privées (inscrits) ═══
const peutVoirInfosPrivees = computed(() =>
    estStaff.value || estCreateur.value
)

// ═══ Inscription possible ? ═══
const inscriptionOuverte = computed(() =>
    statutDynamique.value === 'a_venir' &&
    typeCode.value !== 'SALON' &&
    peutSInscrire.value
)

const formaterHeure = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
    })
}

const lienInscription = computed(() => {
    if (!user.value) {
        // Sauvegarder l'URL actuelle comme "intended" pour redirection post-login
        const urlPreinscription = `/evenements/${props.evenement.id}/preinscrire`
        return `/login?redirect=${encodeURIComponent(urlPreinscription)}`
    }
    return `/evenements/${props.evenement.id}/preinscrire`
})
</script>

<template>
    <component :is="Layout">

        <!-- ═══ BANDEAU DEMANDE DE MODIFICATIONS (pour organisateur) ═══ -->
            <div v-if="evenement.modifications_demandees && evenement.statut === 'brouillon' && estCreateur"
                 class="mx-auto mb-6 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border-l-4 border-amber-500 bg-amber-50 p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-display text-base font-extrabold text-amber-900">
                                Modifications demandées par le responsable
                            </h3>
                            <p class="mt-2 whitespace-pre-line text-sm text-amber-900 bg-white rounded-lg p-3 border border-amber-200">
                                {{ evenement.modifications_demandees }}
                            </p>
                            <p v-if="evenement.modifications_demandees_le" class="mt-2 text-xs text-amber-700">
                                Demandé le {{ new Date(evenement.modifications_demandees_le).toLocaleDateString('fr-FR', {
                                    day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
                                }) }}
                            </p>
                            <p class="mt-3 text-xs text-amber-800">
                                <span class="font-bold">Action attendue :</span>
                                Modifiez votre événement selon les indications, puis redemandez la validation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ════════ HERO BANDEAU ════════ -->
        <section class="relative h-64 overflow-hidden md:h-80">
            <img v-if="evenement.visuel_url" :src="evenement.visuel_url" :alt="evenement.titre"
                class="h-full w-full object-cover" />

            <div v-else :class="['flex h-full w-full items-center justify-center', couleurType(typeCode).bg]">
                <p :class="['font-display text-9xl font-extrabold opacity-20', couleurType(typeCode).text]">
                    {{ evenement.type_evenement?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                </p>
            </div>

            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent" />

            <div class="absolute inset-x-0 bottom-0">
                <div class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="['rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider',
                            couleurType(typeCode).bg, couleurType(typeCode).text]">
                            {{ labelType(typeCode) }}
                        </span>
                        <span :class="['inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider',
                            couleurStatutDyn.bg, couleurStatutDyn.text]">
                            <span :class="['h-1.5 w-1.5 rounded-full', couleurStatutDyn.dot]" />
                            {{ couleurStatutDyn.label }}
                        </span>
                    </div>
                    <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                        {{ evenement.titre }}
                    </h1>
                </div>
            </div>
        </section>

        <!-- ════════ BAR D'ACTIONS ════════ -->
        <section class="border-b border-border-soft bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">

                <Link href="/evenements" class="text-sm font-semibold text-text-sub hover:text-moov-blue">
                     Retour aux événements
                </Link>

                <div class="flex flex-wrap gap-2">

                   <!-- SALON : présence Moov, pas d'inscription -->
                    <div v-if="typeCode === 'SALON'"
                        class="rounded-lg bg-indigo-50 border border-indigo-200 px-5 py-2.5 text-sm font-bold text-indigo-900">
                        Présence Moov sur ce salon
                    </div>

                    <!-- À VENIR + peut s'inscrire -->
                    <template v-else-if="inscriptionOuverte">
                        <!-- Déjà inscrit -->
                        <Link v-if="dejaInscrit && inscriptionId"
                            :href="`/inscriptions/${inscriptionId}`"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Déjà inscrit – Voir mon dossier
                        </Link>
                        <!-- Pas encore inscrit -->
                        <Link v-else :href="lienInscription"
                            class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                            S'inscrire à l'événement
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </Link>
                    </template>

                    <!-- EN COURS -->
                    <span v-else-if="statutDynamique === 'en_cours' && peutSInscrire"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-50 border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">
                        <span class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"/>
                        L'événement a déjà commencé
                    </span>

                    <!-- TERMINÉ -->
                    <span v-else-if="statutDynamique === 'termine' && peutSInscrire"
                        class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm italic text-slate-600">
                        Événement terminé
                    </span>

                    <!-- ANNULÉ -->
                    <span v-else-if="statutDynamique === 'annule' && peutSInscrire"
                        class="rounded-lg bg-red-50 border border-red-200 px-4 py-2.5 text-sm font-bold text-red-700">
                        Événement annulé
                    </span>

                    <!-- PAS ENCORE PUBLIÉ -->
                    <span v-else-if="peutSInscrire && !['publie', 'en_cours', 'termine'].includes(evenement.statut)"
                        class="rounded-lg bg-page-bg px-4 py-2.5 text-sm italic text-text-sub">
                        Inscriptions à venir
                    </span>

                    <template v-if="estStaff">

                        <!-- DEMANDER VALIDATION : seulement organisateur créateur d'un brouillon -->
                        <button v-if="peutDemanderValidation" @click="demanderValidation"
                            class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-amber-600">
                            Demander validation
                        </button>

                        <!-- VALIDER : responsable face à un événement en attente -->
                        <button v-if="peutValider" @click="valider"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Valider et publier
                        </button>
                          <!-- TÉLÉCHARGER LISTE PARTICIPANTS -->
                        <a v-if="estStaff" :href="`/evenements/${evenement.id}/export-participants-pdf`"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            télecharger la Liste des participants
                        </a>
                        <!-- DEMANDER MODIFICATIONS : responsable (alternative au rejet) -->
                        <button v-if="peutDemanderModifs" @click="ouvrirModalModifs"
                            class="inline-flex items-center gap-1.5 rounded-lg border-2 border-amber-300 bg-white px-4 py-2.5 text-sm font-bold text-amber-700 transition hover:bg-amber-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Demander modifications
                        </button>

                        <!-- REJETER : responsable face à un en_validation -->
                        <button v-if="peutRejeter" @click="ouvrirModalRejet"
                            class="rounded-lg border-2 border-red-300 bg-white px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50">
                            Rejeter
                        </button>

                        <!-- GESTION OPÉRATIONNELLE (chrome neutre, icône colorée pour repère rapide) -->
                        <Link :href="`/evenements/${evenement.id}/dashboard`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-moov-blue/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-moov-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m3 6v-2m3 2v-4m1 8H4a2 2 0 01-2-2V6a2 2 0 012-2h16a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                            </svg>
                            Tableau RSE
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/logistique`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-400/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Logistique
                        </Link>

                        <Link v-if="['SPORT', 'HACK', 'CHALLENGE', 'BARA_MOUSSO'].includes(typeCode)"
                            :href="`/evenements/${evenement.id}/competition`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-violet-400/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            Compétition
                        </Link>

                        <!-- SÉPARATEUR -->
                        <div class="h-8 w-px bg-border-soft mx-1"/>

                        <!-- COMMUNICATION & POST-ÉVÉNEMENT -->
                        <Link :href="`/evenements/${evenement.id}/communication/campaigns`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-400/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Campagnes
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/communication/enquetes`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-purple-400/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Enquêtes
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/certificats`"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-400/40 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Certificats
                        </Link>

                        <!-- MODIFIER : créateur seulement (PAS admin) -->
                        <Link v-if="peutModifier" :href="`/evenements/${evenement.id}/edit`"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2.5 text-sm font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                            Modifier
                        </Link>

                        <!-- SUPPRIMER : créateur seulement (PAS admin) -->
                        <button v-if="peutSupprimer" @click="supprimer"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2.5 text-sm font-bold text-red-600 transition hover:border-red-300 hover:bg-red-50">
                            Archiver
                        </button>

                        <!-- BANDEAU ADMIN : indique le mode lecture seule -->
                        <span v-if="estAdmin && !estResponsable"
                              class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Mode supervision
                        </span>

                      
                    </template>
                </div>
            </div>
        </section>

        <!-- ════════ CONTENU ════════ -->
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-6 lg:col-span-2">

                    <!-- KPIs rapides -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Début</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ formaterDate(evenement.date_debut) }}
                            </p>
                            <p v-if="formaterHeure(evenement.date_debut)" class="text-xs text-text-sub">
                                à {{ formaterHeure(evenement.date_debut) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Fin</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ formaterDate(evenement.date_fin) }}</p>
                            <p v-if="formaterHeure(evenement.date_fin)" class="text-xs text-text-sub">
                                à {{ formaterHeure(evenement.date_fin) }}
                            </p>
                        </div>
                       <!-- KPI Inscrits : visible seulement pour staff/créateur -->
                        <div v-if="peutVoirInfosPrivees" class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Inscrits</p>
                            <p class="mt-1 font-display text-xl font-extrabold text-moov-blue">
                                {{ evenement.inscriptions_count ?? 0 }}
                            </p>
                        </div>

                        <!-- Pour le public : afficher la durée à la place -->
                        <div v-else class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Type</p>
                            <p class="mt-1 truncate text-sm font-bold text-text-main">
                                {{ labelType(typeCode) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Lieu</p>
                            <p class="mt-1 truncate text-sm font-bold text-text-main">
                                {{ evenement.lieu?.nom ?? 'À définir' }}
                            </p>
                        </div>
                    </div>

                    <!-- Bandeau SALON -->
                    <div v-if="typeCode === 'SALON'" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-indigo-600 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-indigo-900">Présence Moov sur ce salon</p>
                                <p class="mt-1 text-sm text-indigo-800">
                                    Cet événement est organisé par un tiers. Moov Africa Burkina y tient un stand.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bandeau motif rejet -->
                    <div v-if="evenement.statut === 'brouillon' && evenement.motif_rejet"
                        class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-bold text-red-900">Événement rejeté par le responsable</p>
                        <p class="mt-1 text-sm text-red-800">{{ evenement.motif_rejet }}</p>
                    </div>

                    <!-- Bandeau en validation -->
                    <div v-if="evenement.statut === 'en_validation'"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-bold text-amber-900">En attente de validation</p>
                        <p class="mt-1 text-xs text-amber-800">
                            Cet événement attend l'approbation du responsable dCIRP avant publication.
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">À propos</h2>
                        </div>
                        <div class="p-5">
                            <p class="whitespace-pre-line leading-relaxed text-text-sub">
                                {{ evenement.description || 'Aucune description disponible.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Règlement PDF -->
                    <div v-if="evenement.reglement_pdf_url" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-red-100">
                                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-display text-base font-bold text-text-main">Règlement & consignes
                                    </h3>
                                    <p class="mt-1 text-sm text-amber-900">
                                         Document officiel de l'évènement.
                                    </p>
                                </div>
                            </div>
                            <a :href="evenement.reglement_pdf_url" target="_blank" download
                                class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                                Télécharger le PDF
                            </a>
                        </div>
                    </div>

                    <!-- ════════ BUDGET ════════ -->
                    <div v-if="peutGererBudget && budget" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">Budget de l'événement</h2>
                            <p class="mt-1 text-xs text-text-sub">Suivi des recettes et dépenses réelles</p>
                        </div>

                        <!-- Résumé -->
                        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Prévisionnel</p>
                                <p class="mt-1 text-base font-extrabold text-text-main">{{ formaterFCFA(budget.montant_previsionnel) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Recettes</p>
                                <p class="mt-1 text-base font-extrabold text-emerald-600">{{ formaterFCFA(budget.recettes) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Dépenses</p>
                                <p class="mt-1 text-base font-extrabold text-red-600">{{ formaterFCFA(budget.depenses) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Solde</p>
                                <p class="mt-1 text-base font-extrabold"
                                   :class="budget.solde >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                    {{ formaterFCFA(budget.solde) }}
                                </p>
                            </div>
                        </div>

                        <!-- Lignes -->
                        <div v-if="budget.lignes.length" class="divide-y divide-border-soft border-t border-border-soft">
                            <div v-for="ligne in budget.lignes" :key="ligne.id"
                                 class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-text-main">{{ ligne.libelle }}</p>
                                    <span :class="['text-xs font-bold uppercase tracking-wider',
                                        ligne.type === 'recette' ? 'text-emerald-600' : 'text-red-600']">
                                        {{ ligne.type === 'recette' ? 'Recette' : 'Dépense' }}
                                    </span>
                                </div>
                                <span class="text-sm font-bold text-text-main">{{ formaterFCFA(ligne.montant) }}</span>
                                <button @click="supprimerLigneBudget(ligne)" type="button"
                                        class="rounded-lg p-1.5 text-text-sub transition hover:bg-red-50 hover:text-red-600"
                                        title="Supprimer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Ajouter une ligne -->
                        <form @submit.prevent="ajouterLigneBudget"
                              class="flex flex-wrap items-end gap-3 border-t border-border-soft p-5">
                            <div class="min-w-[10rem] flex-1">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">Libellé</label>
                                <input v-model="ligneForm.libelle" type="text" required
                                       class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div class="w-32">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">Montant</label>
                                <input v-model.number="ligneForm.montant" type="number" min="0" step="1" required
                                       class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div class="w-36">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">Type</label>
                                <select v-model="ligneForm.type"
                                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                    <option value="depense">Dépense</option>
                                    <option value="recette">Recette</option>
                                </select>
                            </div>
                            <button type="submit" :disabled="ligneForm.processing"
                                    class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:cursor-not-allowed disabled:opacity-50">
                                Ajouter
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ════════ SIDEBAR ════════ -->
                <aside class="space-y-6">

                    <!-- Lieu -->
                    <div v-if="evenement.lieu" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">Lieu</h3>
                        </div>
                        <div class="p-5">
                            <p class="font-bold text-text-main">{{ evenement.lieu.nom }}</p>
                            <p v-if="evenement.lieu.adresse" class="mt-1 text-sm text-text-sub">
                                {{ evenement.lieu.adresse }}
                            </p>
                        </div>
                    </div>

                    <!-- Tarifs -->
                    <div v-if="evenement.tarifs?.length" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">Tarifs
                            </h3>
                        </div>
                        <div class="p-5 space-y-2">
                            <div v-for="t in evenement.tarifs" :key="t.id"
                                class="flex items-center justify-between rounded-lg bg-page-bg p-3">
                                <span class="text-sm font-medium text-text-main">{{ t.nom ?? t.libelle }}</span>
                                <span :class="['font-display font-extrabold',
                                    t.montant > 0 ? 'text-moov-blue' : 'text-emerald-600']">
                                    <span v-if="t.montant > 0">
                                        {{ Number(t.montant).toLocaleString('fr-FR') }}
                                        <span class="text-xs">FCFA</span>
                                    </span>
                                    <span v-else>Gratuit</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ DEVENIR BÉNÉVOLE ════════ -->
                    <div v-if="postesBenevoles.length > 0 && estParticipant && ['publie', 'en_cours'].includes(evenement.statut)"
                        class="rounded-xl bg-white shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                                Devenir bénévole
                            </h3>
                            <p class="mt-1 text-xs text-text-muted">
                                {{ postesBenevoles.length }} poste{{ postesBenevoles.length > 1 ? 's' : '' }} ouvert{{
                                postesBenevoles.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="space-y-3 p-5">
                            <div v-for="poste in postesBenevoles" :key="poste.id"
                                class="rounded-lg border border-border-soft p-4">
                                <div class="min-w-0">
                                    <p class="font-bold text-text-main">{{ poste.nom_poste }}</p>
                                    <p class="mt-1 text-xs text-text-sub line-clamp-2">{{ poste.description }}</p>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <span
                                        class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">
                                        {{ poste.places_restantes }} place{{ poste.places_restantes > 1 ? 's' : '' }}
                                    </span>
                                    <button @click="ouvrirBenevolat(poste)"
                                        class="rounded-lg bg-moov-orange px-3 py-1.5 text-xs font-bold text-white transition hover:bg-orange-600">
                                        Postuler 
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <!-- ════════ MODALE REJET ÉVÉNEMENT ════════ -->
        <div v-if="modalRejetOuvert" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="modalRejetOuvert = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Rejeter l'événement</h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Indiquez le motif du rejet pour que l'organisateur puisse corriger.
                    </p>
                </div>
                <div class="p-5">
                    <textarea v-model="motifRejet" rows="4"
                        placeholder="Ex: Le budget prévisionnel manque de précision..."
                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                    <p class="mt-1 text-xs text-text-muted">{{ motifRejet.length }} caractères (min. 10)</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRejetOuvert = false"
                        class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRejet" :disabled="motifRejet.length < 10"
                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-50">
                        Rejeter
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════ MODALE BÉNÉVOLAT ════════ -->
        <div v-if="modalBenevolatOuvert"
            class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
            @click.self="modalBenevolatOuvert = false">
            <div class="my-8 w-full max-w-xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">
                            Candidater au poste
                        </h3>
                        <p class="mt-1 text-sm text-moov-orange font-bold">
                            {{ posteSelectionne?.nom_poste }}
                        </p>
                    </div>
                    <button @click="modalBenevolatOuvert = false" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="posteSelectionne" class="border-b border-border-soft bg-page-bg/50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Description du poste</p>
                    <p class="mt-1 text-sm text-text-sub">{{ posteSelectionne.description }}</p>
                    <p v-if="posteSelectionne.competences_requises" class="mt-2 text-xs text-text-sub">
                        <strong>Compétences :</strong> {{ posteSelectionne.competences_requises }}
                    </p>
                </div>

                <form @submit.prevent="candidater" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Pourquoi ce poste vous intéresse ? * (min. 30 caractères)
                        </label>
                        <textarea v-model="formBenevolat.motivation" rows="4" required
                            placeholder="Expliquez votre motivation à devenir bénévole..."
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                        <p class="mt-1 text-xs text-text-muted">
                            {{ formBenevolat.motivation.length }} caractères
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Expérience pertinente
                        </label>
                        <textarea v-model="formBenevolat.experience" rows="2"
                            placeholder="Vos expériences passées, compétences..."
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Disponibilités
                        </label>
                        <input v-model="formBenevolat.disponibilites" type="text"
                            placeholder="Ex: Toute la journée du 15 mai"
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="modalBenevolatOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit"
                            :disabled="formBenevolat.processing || formBenevolat.motivation.length < 30"
                            class="rounded-lg bg-moov-orange px-5 py-2 text-sm font-bold text-white transition hover:bg-orange-600 disabled:opacity-50">
                            {{ formBenevolat.processing ? 'Envoi...' : 'Envoyer ma candidature' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
<!-- Modal de confirmation -->
        <ConfirmModal
            :show="modalConfirm.show"
            :type="modalConfirm.type"
            :title="modalConfirm.title"
            :message="modalConfirm.message"
            :confirm-text="modalConfirm.confirmText"
            @confirm="executerAction"
            @cancel="fermerModal"
        />
    </component>

    <!-- ═══ MODALE DEMANDER MODIFICATIONS ═══ -->
        <Teleport to="body">
            <div v-if="modalModifs" 
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
                 @click.self="fermerModalModifs">

                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                    <!-- Header -->
                    <div class="border-b border-slate-200 p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-display text-lg font-extrabold text-slate-900">
                                    Demander des modifications
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Précisez à l'organisateur ce qu'il doit modifier avant publication.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Modifications à apporter <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="messageModifications" rows="6"
                            placeholder="Ex: Merci de préciser la date de clôture des inscriptions, d'ajouter le règlement complet et de corriger le budget prévisionnel..."
                            class="w-full rounded-lg border-2 border-slate-200 px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"
                            maxlength="2000"/>

                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span :class="messageModifications.length < 10 ? 'text-red-600' : 'text-slate-500'">
                                {{ messageModifications.length < 10 ? 'Minimum 10 caractères' : 'Message valide' }}
                            </span>
                            <span class="text-slate-500">{{ messageModifications.length }} / 2000</span>
                        </div>

                        <!-- Info -->
                        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
                            <p class="font-bold">Conséquences de cette action :</p>
                            <ul class="mt-1 list-disc list-inside space-y-0.5">
                                <li>L'événement repassera en statut <strong>Brouillon</strong></li>
                                <li>L'organisateur sera notifié de votre demande</li>
                                <li>Il pourra modifier puis redemander une validation</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 rounded-b-2xl">
                        <button @click="fermerModalModifs"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                            Annuler
                        </button>
                        <button @click="envoyerDemandeModifs"
                            :disabled="messageModifications.length < 10"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Envoyer la demande
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
</template>