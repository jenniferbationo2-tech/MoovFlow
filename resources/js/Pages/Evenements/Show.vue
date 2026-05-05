<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: Object,
    budget: Object,
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
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

const typeCode = computed(() => props.evenement.type_evenement?.code)

// ── HELPERS ──────────────────────────────────

const couleurType = (code) => ({
    BARA_MOUSSO: { bg: 'bg-amber-50', text: 'text-amber-700', accent: 'bg-amber-600' },
    CONF: { bg: 'bg-rose-50', text: 'text-rose-700', accent: 'bg-rose-600' },
    SPORT: { bg: 'bg-blue-50', text: 'text-blue-700', accent: 'bg-blue-600' },
    CHALLENGE: { bg: 'bg-violet-50', text: 'text-violet-700', accent: 'bg-violet-600' },
    FORMATION: { bg: 'bg-emerald-50', text: 'text-emerald-700', accent: 'bg-emerald-600' },
    HACK: { bg: 'bg-orange-50', text: 'text-orange-700', accent: 'bg-orange-600' },
    SALON: { bg: 'bg-indigo-50', text: 'text-indigo-700', accent: 'bg-indigo-600' },
    ATELIER: { bg: 'bg-teal-50', text: 'text-teal-700', accent: 'bg-teal-600' },
    WEBINAIRE: { bg: 'bg-purple-50', text: 'text-purple-700', accent: 'bg-purple-600' },
}[code] || { bg: 'bg-slate-50', text: 'text-slate-700', accent: 'bg-slate-600' })

const couleurStatut = (statut) => ({
    publie: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publié' },
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
    return `/evenements/${props.evenement.id}/inscrire`
})

const publier = () => {
    if (confirm('Publier cet événement ?')) {
        router.post(`/evenements/${props.evenement.id}/publish`)
    }
}

const supprimer = () => {
    if (confirm('Archiver cet événement ?')) {
        router.delete(`/evenements/${props.evenement.id}`)
    }
}
</script>

<template>
    <component :is="Layout">

        <!-- ═════════ BANNIÈRE SOBRE ═════════ -->
        <section class="relative h-64 overflow-hidden md:h-80">
            <img v-if="evenement.visuel_url" :src="evenement.visuel_url" :alt="evenement.titre"
                class="h-full w-full object-cover" />

            <!-- Placeholder sobre si pas d'image -->
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


        <section class="border-b border-border-soft bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">

                <Link href="/evenements" class="text-sm font-semibold text-text-sub hover:text-moov-blue">
                    ← Retour aux événements
                </Link>

                <div class="flex flex-wrap gap-2">
                    <!-- Participant -->
                    <Link v-if="peutSInscrire && evenement.statut === 'publie'" :href="lienInscription"
                        class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        S'inscrire à l'événement →
                    </Link>
                    <span v-else-if="peutSInscrire && evenement.statut !== 'publie'"
                        class="rounded-lg bg-page-bg px-4 py-2.5 text-sm italic text-text-sub">
                        Inscriptions fermées
                    </span>

                    <!-- Staff -->
                    <template v-if="estOrganisateur">
                        <button v-if="evenement.statut === 'brouillon'" @click="publier"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                            Publier
                        </button>

                        <!-- Bouton Logistique -->
                        <Link :href="`/evenements/${evenement.id}/logistique-v2`"
                            class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-amber-700">
                            Logistique
                        </Link>

                        <!--Bouton Compétition (uniquement pour types compétitifs) -->
                        <Link v-if="['SPORT', 'HACK', 'CHALLENGE', 'BARA_MOUSSO'].includes(typeCode)"
                            :href="`/evenements/${evenement.id}/competition`"
                            class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700">
                            Compétition
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


        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-6 lg:col-span-2">

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Début</p>
                            <p class="mt-1 text-sm font-bold text-text-main">
                                {{ formaterDate(evenement.date_debut) }}
                            </p>
                            <p v-if="formaterHeure(evenement.date_debut)" class="text-xs text-text-sub">
                                à {{ formaterHeure(evenement.date_debut) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-border-soft bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Fin</p>
                            <p class="mt-1 text-sm font-bold text-text-main">
                                {{ formaterDate(evenement.date_fin) }}
                            </p>
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


                    <div v-if="evenement.reglement_pdf_url" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-red-100">
                                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-display text-base font-bold text-text-main">
                                        Règlement & consignes
                                    </h3>
                                    <p class="mt-1 text-sm text-amber-900">
                                        Téléchargez le document officiel avant de soumettre votre dossier.
                                    </p>
                                </div>
                            </div>

                            <a :href="evenement.reglement_pdf_url" target="_blank" download
                                class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Télécharger le PDF
                            </a>
                        </div>
                    </div>

                    <!-- ════════ SECTION SALON (sobre) ════════ -->
                    <div v-if="typeCode === 'SALON'" class="rounded-xl border border-indigo-200 bg-white shadow-card">
                        <div class="border-b border-indigo-200 bg-indigo-50/50 p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="font-display text-base font-bold text-text-main">
                                    Stand Moov sur le salon
                                </h2>
                                <span
                                    class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                                    Info clé
                                </span>
                            </div>
                        </div>

                        <div class="space-y-5 p-5">
                            <!-- Emplacement (le plus important) -->
                            <div v-if="evenement.lieu_stand"
                                class="rounded-lg border-2 border-moov-orange bg-moov-orange-50 p-4 text-center">
                                <p class="text-xs font-bold uppercase tracking-wider text-moov-orange-dark">
                                    Emplacement du stand
                                </p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-moov-orange-dark">
                                    {{ evenement.lieu_stand }}
                                </p>
                                <p v-if="evenement.superficie_stand" class="mt-1 text-sm text-text-sub">
                                    Superficie : {{ evenement.superficie_stand }} m²
                                </p>
                            </div>

                            <!-- Infos hôte -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div v-if="evenement.nom_salon_hote">
                                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Salon hôte</p>
                                    <p class="mt-1 font-bold text-text-main">{{ evenement.nom_salon_hote }}</p>
                                </div>
                                <div v-if="evenement.organisateur_externe">
                                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Organisé par
                                    </p>
                                    <p class="mt-1 font-bold text-text-main">{{ evenement.organisateur_externe }}</p>
                                </div>
                            </div>

                            <!-- Objectifs -->
                            <div v-if="evenement.objectifs_stand">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Objectifs du stand
                                </p>
                                <p class="mt-1 text-sm text-text-sub">{{ evenement.objectifs_stand }}</p>
                            </div>

                            <!-- Objectif prospects -->
                            <div v-if="evenement.objectif_prospects && estOrganisateur"
                                class="rounded-lg border border-border-soft bg-page-bg p-4 text-center">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    Objectif de collecte
                                </p>
                                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">
                                    {{ evenement.objectif_prospects }}
                                </p>
                                <p class="text-xs text-text-sub">prospects à contacter</p>
                            </div>
                        </div>
                    </div>


                    <div v-if="evenement.prix?.length" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">
                                Prix à remporter
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div v-for="p in evenement.prix" :key="p.id" :class="['rounded-lg border p-4 text-center transition',
                                    p.rang === 1 ? 'border-amber-300 bg-amber-50'
                                        : p.rang === 2 ? 'border-slate-300 bg-slate-50'
                                            : 'border-orange-300 bg-orange-50']">
                                    <p :class="['text-xs font-bold uppercase tracking-wider',
                                        p.rang === 1 ? 'text-amber-700'
                                            : p.rang === 2 ? 'text-slate-700'
                                                : 'text-orange-700']">
                                        {{ p.rang === 1 ? '1er prix' : p.rang === 2 ? '2ème prix' : `${p.rang}ème prix`
                                        }}
                                    </p>
                                    <p class="mt-2 font-bold text-text-main">{{ p.libelle }}</p>
                                    <p v-if="p.nature_prix" class="mt-1 text-sm text-text-sub">{{ p.nature_prix }}</p>
                                    <p v-if="p.valeur_monetaire > 0"
                                        class="mt-3 font-display text-lg font-extrabold text-emerald-700">
                                        {{ Number(p.valeur_monetaire).toLocaleString('fr-FR') }} <span
                                            class="text-xs">FCFA</span>
                                    </p>
                                    <span v-if="p.attribue"
                                        class="mt-2 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">
                                        Attribué
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ INTERVENANTS (sobre) ════════ -->
                    <div v-if="evenement.intervenants?.length && ['CONF', 'FORMATION', 'BARA_MOUSSO'].includes(typeCode)"
                        class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">
                                {{ typeCode === 'BARA_MOUSSO' ? 'Membres du jury' :
                                    typeCode === 'FORMATION' ? 'Formateurs' : 'Intervenants' }}
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                <div v-for="i in evenement.intervenants" :key="i.id"
                                    class="flex items-center gap-3 rounded-lg border border-border-soft bg-page-bg/50 p-3">
                                    <img v-if="i.photo" :src="i.photo" :alt="i.nom"
                                        class="h-10 w-10 rounded-full object-cover" />
                                    <div v-else
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                        {{ i.prenom?.[0] }}{{ i.nom?.[0] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-text-main">
                                            {{ i.prenom }} {{ i.nom }}
                                        </p>
                                        <p v-if="i.specialite" class="truncate text-xs text-text-sub">
                                            {{ i.specialite }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SESSIONS (organisateur) ════════ -->
                    <div v-if="evenement.sessions?.length && estOrganisateur" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">
                                Sessions planifiées
                            </h2>
                        </div>
                        <div class="p-5 space-y-2">
                            <div v-for="s in evenement.sessions" :key="s.id"
                                class="flex items-center gap-3 rounded-lg border border-border-soft bg-page-bg/50 p-3">
                                <div
                                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-moov-blue-50 text-moov-blue">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-text-main">{{ s.titre }}</p>
                                    <p class="text-xs text-text-sub">
                                        {{ formaterDate(s.heure_debut) }} à {{ formaterHeure(s.heure_debut) }}
                                        <span v-if="s.salle"> · Salle {{ s.salle.nom }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ BÉNÉVOLES (organisateur) ════════ -->
                    <div v-if="evenement.benevoles?.length && estOrganisateur" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h2 class="font-display text-base font-bold text-text-main">
                                Bénévoles ({{ evenement.benevoles.length }})
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div v-for="b in evenement.benevoles" :key="b.id"
                                    class="flex items-center gap-3 rounded-lg border border-border-soft bg-page-bg/50 p-3">
                                    <div
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">
                                        {{ b.prenom?.[0] }}{{ b.nom?.[0] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-text-main">{{ b.prenom }} {{ b.nom }}</p>
                                        <p v-if="b.poste_affecte" class="text-xs text-text-sub">{{ b.poste_affecte }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <aside class="space-y-6">

                    <!-- Lieu détaillé -->
                    <div v-if="evenement.lieu" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                                Lieu
                            </h3>
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
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                                Tarifs
                            </h3>
                        </div>
                        <div class="p-5 space-y-2">
                            <div v-for="t in evenement.tarifs" :key="t.id"
                                class="flex items-center justify-between rounded-lg bg-page-bg p-3">
                                <span class="text-sm font-medium text-text-main">{{ t.libelle }}</span>
                                <span :class="['font-display font-extrabold',
                                    t.montant > 0 ? 'text-moov-blue' : 'text-emerald-600']">
                                    <span v-if="t.montant > 0">
                                        {{ Number(t.montant).toLocaleString('fr-FR') }}
                                        <span class="text-xs">{{ t.devise }}</span>
                                    </span>
                                    <span v-else>Gratuit</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Budget (organisateur) -->
                    <div v-if="estOrganisateur && budget" class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                                Budget
                            </h3>
                        </div>
                        <div class="p-5 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-text-sub">Prévisionnel</span>
                                <span class="font-bold text-text-main">
                                    {{ Number(budget.montant_previsionnel ?? 0).toLocaleString('fr-FR') }} {{
                                        budget.devise }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text-sub">Engagé</span>
                                <span class="font-bold text-red-600">
                                    {{ Number(budget.depenses_engagees ?? 0).toLocaleString('fr-FR') }} {{ budget.devise
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between border-t border-border-soft pt-2">
                                <span class="font-bold text-text-main">Solde</span>
                                <span class="font-display font-extrabold text-emerald-600">
                                    {{ Number((budget.montant_previsionnel ?? 0) - (budget.depenses_engagees ??
                                        0)).toLocaleString('fr-FR') }} {{ budget.devise }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- RSE (organisateur) -->
                    <div v-if="evenement.objectifs_rse?.length && estOrganisateur"
                        class="rounded-xl bg-card shadow-card">
                        <div class="border-b border-border-soft p-5">
                            <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                                Impact RSE
                            </h3>
                        </div>
                        <div class="p-5 space-y-2 text-sm">
                            <div v-for="o in evenement.objectifs_rse" :key="o.id" class="space-y-1.5">
                                <div v-if="o.nb_beneficiaires_directs" class="flex justify-between">
                                    <span class="text-text-sub">Bénéficiaires</span>
                                    <span class="font-bold text-text-main">{{ o.nb_beneficiaires_directs }}</span>
                                </div>
                                <div v-if="o.nb_femmes_beneficiaires" class="flex justify-between">
                                    <span class="text-text-sub">Dont femmes</span>
                                    <span class="font-bold text-text-main">{{ o.nb_femmes_beneficiaires }}</span>
                                </div>
                                <div v-if="o.score_environnemental"
                                    class="flex justify-between border-t border-border-soft pt-2">
                                    <span class="text-text-sub">Score env.</span>
                                    <span class="font-bold text-emerald-700">{{ o.score_environnemental }}/100</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </aside>
            </div>
        </section>

    </component>
</template>