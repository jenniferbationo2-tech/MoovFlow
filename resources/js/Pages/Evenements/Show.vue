<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: Object,
    budget: Object,
    postesBenevoles: { type: Array, default: () => [] },
})

// ══════════════════════════════════════
//  USER & ROLES
// ══════════════════════════════════════
const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const userId = computed(() => page.props.auth?.user?.id)
const userRoles = computed(() => page.props.auth?.user?.roles?.map(r => r.name) ?? [])
const roles = computed(() => user.value?.roles ?? [])

const estStaff = computed(() =>
    roles.value.some(r => ['admin', 'responsable_dcirp', 'organisateur'].includes(r))
)
const Layout = computed(() => estStaff.value ? DashboardLayout : PublicLayout)

const estOrganisateur = computed(() =>
    roles.value.some(r => ['organisateur', 'admin', 'responsable_dcirp'].includes(r))
)
const peutSInscrire = computed(() =>
    !user.value || roles.value.includes('participant') || roles.value.length === 0
)

// PERMISSIONS
const estAdmin = computed(() => userRoles.value.includes('admin'))
const estResponsable = computed(() =>
    userRoles.value.includes('responsable_dcirp') || userRoles.value.includes('admin')
)
const estCreateur = computed(() => props.evenement?.created_by === userId.value)

const estParticipant = computed(() =>
    userRoles.value.includes('participant') &&
    !userRoles.value.includes('admin') &&
    !userRoles.value.includes('responsable_dcirp')
)

// ══════════════════════════════════════
//  WORKFLOW ÉVÉNEMENT
// ══════════════════════════════════════
const demanderValidation = () => {
    if (confirm('Envoyer cet événement en validation au responsable dCIRP ?')) {
        router.post(`/evenements/${props.evenement.id}/demander-validation`, {}, { preserveScroll: true })
    }
}

const valider = () => {
    if (confirm('Valider et publier cet événement ?')) {
        router.post(`/evenements/${props.evenement.id}/valider`, {}, { preserveScroll: true })
    }
}

const modalRejetOuvert = ref(false)
const motifRejet = ref('')
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

const supprimer = () => {
    if (confirm('Archiver cet événement ?')) {
        router.delete(`/evenements/${props.evenement.id}`)
    }
}

// ══════════════════════════════════════
//  BÉNÉVOLAT
// ══════════════════════════════════════
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

// ══════════════════════════════════════
//  HELPERS UI
// ══════════════════════════════════════
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

const formaterHeure = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
    })
}

const lienInscription = computed(() => {
    if (!user.value) return '/login'
    return `/evenements/${props.evenement.id}/preinscrire`
})
</script>

<template>
    <component :is="Layout">

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
                            couleurStatut(evenement.statut).bg, couleurStatut(evenement.statut).text]">
                            <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(evenement.statut).dot]" />
                            {{ couleurStatut(evenement.statut).label }}
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
                    ← Retour aux événements
                </Link>

                <div class="flex flex-wrap gap-2">

                    <div v-if="typeCode === 'SALON'"
                        class="rounded-lg bg-indigo-50 border border-indigo-200 px-5 py-2.5 text-sm font-bold text-indigo-900">
                        Présence Moov sur ce salon
                    </div>

                    <Link v-else-if="peutSInscrire && evenement.statut === 'publie'" :href="lienInscription"
                        class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        S'inscrire à l'événement →
                    </Link>
                    <span v-else-if="peutSInscrire && evenement.statut !== 'publie'"
                        class="rounded-lg bg-page-bg px-4 py-2.5 text-sm italic text-text-sub">
                        Inscriptions fermées
                    </span>

                    <template v-if="estOrganisateur">
                        <button v-if="evenement.statut === 'brouillon' && estCreateur" @click="demanderValidation"
                            class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-amber-600">
                            Demander validation
                        </button>

                        <button v-if="['brouillon', 'en_validation'].includes(evenement.statut) && estResponsable"
                            @click="valider"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                            Valider et publier
                        </button>

                        <button v-if="evenement.statut === 'en_validation' && estResponsable" @click="ouvrirModalRejet"
                            class="rounded-lg border-2 border-red-300 bg-white px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50">
                            Rejeter
                        </button>

                        <Link :href="`/evenements/${evenement.id}/logistique`"
                            class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-amber-700">
                            Logistique
                        </Link>
                        <Link :href="`/evenements/${evenement.id}/dashboard`"
                            class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
                            Tableau
                        </Link>

                        <Link v-if="['SPORT', 'HACK', 'CHALLENGE', 'BARA_MOUSSO'].includes(typeCode)"
                            :href="`/evenements/${evenement.id}/competition`"
                            class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700">
                            Compétition
                        </Link>
                        <Link :href="`/evenements/${evenement.id}/communication/campaigns`"
                            class="rounded-lg bg-cyan-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyan-700">
                             Campagnes
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/communication/enquetes`"
                            class="rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-purple-700">
                             Enquêtes
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/certificats`"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                             Certificats
                        </Link>

                        <Link :href="`/evenements/${evenement.id}/edit`"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2.5 text-sm font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                            Modifier
                        </Link>

                        <button @click="supprimer"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2.5 text-sm font-bold text-red-600 transition hover:border-red-300 hover:bg-red-50">
                            Archiver
                        </button>
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
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Inscrits</p>
                            <p class="mt-1 font-display text-xl font-extrabold text-moov-blue">
                                {{ evenement.inscriptions_count ?? 0 }}
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
                                        Téléchargez le document officiel avant de soumettre votre dossier.
                                    </p>
                                </div>
                            </div>
                            <a :href="evenement.reglement_pdf_url" target="_blank" download
                                class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                                Télécharger le PDF
                            </a>
                        </div>
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
                    <div v-if="postesBenevoles.length > 0 && estParticipant && evenement.statut === 'publie'"
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
                                        Postuler →
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

    </component>
</template>