<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    lieux:                  { type: Array, required: true },
    materiels:              { type: Array, required: true },
    prestataires:           { type: Array, required: true },
    postes:                 { type: Array, required: true },
    categoriesMateriel:     { type: Object, required: true },
    etatsMateriel:          { type: Object, required: true },
    categoriesPrestataire:  { type: Object, required: true },
    kpis:                   { type: Object, required: true },
    permissions:            { type: Object, required: true },
})

// ═════════ ONGLETS ═════════
const ongletActif = ref('lieux')

const onglets = [
    { key: 'lieux',         label: 'Salles & Espaces',  icon: 'pin' },
    { key: 'materiels',     label: 'Matériel',           icon: 'screen' },
    { key: 'prestataires',  label: 'Prestataires',       icon: 'truck' },
    { key: 'benevoles',     label: 'Bénévoles',          icon: 'users' },
]

// ═════════ MODALES ═════════
const modaleOuverte = ref(null) // 'lieu' | 'materiel' | 'prestataire' | null

const ouvrirModale = (type) => {
    modaleOuverte.value = type
    // Reset les forms
    if (type === 'lieu') formLieu.reset()
    if (type === 'materiel') formMateriel.reset()
    if (type === 'prestataire') formPrestataire.reset()
}

const fermerModale = () => {
    modaleOuverte.value = null
}

// ═════════ FORMS ═════════

const formLieu = useForm({
    nom: '', adresse: '', ville: '', capacite_max: '', description: '', actif: true,
})

const formMateriel = useForm({
    nom: '', categorie: '', description: '', quantite_totale: '',
    unite: 'pièce', etat: 'bon', lieu_stockage: '',
})

const formPrestataire = useForm({
    nom: '', categorie: '', contact_nom: '', email: '', telephone: '',
    ville: '', description: '', note_interne: '', actif: true,
})

// ═════════ SUBMITS ═════════

const creerLieu = () => {
    formLieu.post('/lieux', {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
    })
}

const creerMateriel = () => {
    formMateriel.post('/materiels', {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
    })
}

const creerPrestataire = () => {
    formPrestataire.post('/prestataires', {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
    })
}

// ═════════ SUPPRESSION ═════════

const supprimer = (type, item) => {
    const labels = {
        lieu: 'ce lieu',
        materiel: 'ce matériel',
        prestataire: 'ce prestataire',
    }
    if (!confirm(`Supprimer ${labels[type]} : "${item.nom}" ?`)) return

    const urls = {
        lieu: `/lieux/${item.id}`,
        materiel: `/materiels/${item.id}`,
        prestataire: `/prestataires/${item.id}`,
    }
    router.delete(urls[type], { preserveScroll: true })
}

// ═════════ HELPERS UI ═════════

const labelEtatMateriel = (etat) => props.etatsMateriel[etat] ?? etat
const labelCategorieMateriel = (cat) => props.categoriesMateriel[cat] ?? cat
const labelCategoriePrestataire = (cat) => props.categoriesPrestataire[cat] ?? cat

const couleurEtatMateriel = (etat) => ({
    neuf:  { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    bon:   { bg: 'bg-blue-50',    text: 'text-blue-700' },
    usage: { bg: 'bg-amber-50',   text: 'text-amber-700' },
    hs:    { bg: 'bg-rose-50',    text: 'text-rose-700' },
}[etat] || { bg: 'bg-slate-50', text: 'text-slate-700' })

const statutLieu = (lieu) => {
    if (!lieu.actif) return { label: 'Inactif', bg: 'bg-slate-100', text: 'text-slate-700' }
    return { label: 'Disponible', bg: 'bg-emerald-50', text: 'text-emerald-700' }
}

const statutPoste = (poste) => ({
    ouvert:  { label: 'Ouvert',  bg: 'bg-emerald-50', text: 'text-emerald-700' },
    ferme:   { label: 'Fermé',   bg: 'bg-slate-100',  text: 'text-slate-700' },
    complet: { label: 'Complet', bg: 'bg-blue-50',    text: 'text-blue-700' },
}[poste.statut] || { label: poste.statut, bg: 'bg-slate-100', text: 'text-slate-700' })
</script>

<template>
    <DashboardLayout>

        <!-- En-tête -->
        <div class="mb-6">
            <h1 class="font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Gestion Logistique
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Gérez les ressources, les espaces et les équipes pour vos événements.
            </p>
        </div>

        <!-- ═════════ ONGLETS ═════════ -->
        <div class="rounded-2xl bg-white shadow-card">

            <div class="border-b border-border-soft">
                <nav class="flex flex-wrap gap-1 px-2 sm:px-4">
                    <button v-for="o in onglets" :key="o.key"
                            @click="ongletActif = o.key"
                            :class="['flex items-center gap-2 border-b-2 px-4 py-4 text-sm font-bold transition',
                                ongletActif === o.key
                                    ? 'border-moov-blue text-moov-blue'
                                    : 'border-transparent text-text-sub hover:text-text-main']">

                        <!-- Icônes SVG -->
                        <svg v-if="o.icon === 'pin'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0L6.343 16.657a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg v-else-if="o.icon === 'screen'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <svg v-else-if="o.icon === 'truck'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
                        </svg>
                        <svg v-else-if="o.icon === 'users'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>

                        {{ o.label }}
                    </button>
                </nav>
            </div>

            <!-- ═════════ CONTENU ONGLET : LIEUX ═════════ -->
            <div v-if="ongletActif === 'lieux'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ lieux.length }} salle(s) & espace(s) au catalogue
                    </p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('lieu')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Ajouter un lieu
                    </button>
                </div>

                <div v-if="lieux.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="lieu in lieux" :key="lieu.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ lieu.nom }}
                                </h3>
                                <p v-if="lieu.capacite_max" class="mt-1 flex items-center gap-1.5 text-xs text-text-sub">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Capacité : {{ lieu.capacite_max }} places
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                statutLieu(lieu).bg, statutLieu(lieu).text]">
                                {{ statutLieu(lieu).label }}
                            </span>
                        </div>

                        <p v-if="lieu.description" class="mt-3 text-sm text-text-sub">
                            {{ lieu.description }}
                        </p>

                        <div v-if="lieu.adresse || lieu.ville" class="mt-3 flex items-center gap-1.5 text-xs text-text-sub">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0L6.343 16.657a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ [lieu.adresse, lieu.ville].filter(Boolean).join(', ') }}</span>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-border-soft pt-3">
                            <Link :href="`/lieux/${lieu.id}`"
                                  class="text-xs font-bold text-moov-blue hover:underline">
                                Voir les détails →
                            </Link>
                            <button v-if="permissions.peut_supprimer" @click="supprimer('lieu', lieu)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun lieu dans le catalogue</p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('lieu')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Ajouter le premier lieu
                    </button>
                </div>
            </div>

            <!-- ═════════ CONTENU ONGLET : MATÉRIEL ═════════ -->
            <div v-else-if="ongletActif === 'materiels'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ materiels.length }} équipement(s) au catalogue
                    </p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('materiel')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Ajouter du matériel
                    </button>
                </div>

                <div v-if="materiels.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="m in materiels" :key="m.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ m.nom }}
                                </h3>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-text-muted">
                                    {{ labelCategorieMateriel(m.categorie) }}
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                couleurEtatMateriel(m.etat).bg, couleurEtatMateriel(m.etat).text]">
                                {{ labelEtatMateriel(m.etat) }}
                            </span>
                        </div>

                        <p v-if="m.description" class="mt-3 text-sm text-text-sub">
                            {{ m.description }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ m.quantite_totale }} {{ m.unite }}{{ m.quantite_totale > 1 ? 's' : '' }} total
                            </span>
                            <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                {{ m.quantite_disponible }} dispo
                            </span>
                            <span v-if="m.lieu_stockage"
                                  class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                📍 {{ m.lieu_stockage }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-border-soft pt-3">
                            <Link :href="`/materiels/${m.id}`"
                                  class="text-xs font-bold text-moov-blue hover:underline">
                                Voir les détails →
                            </Link>
                            <button v-if="permissions.peut_supprimer" @click="supprimer('materiel', m)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun matériel dans le catalogue</p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('materiel')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Ajouter le premier matériel
                    </button>
                </div>
            </div>

            <!-- ═════════ CONTENU ONGLET : PRESTATAIRES ═════════ -->
            <div v-else-if="ongletActif === 'prestataires'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ prestataires.length }} prestataire(s) actif(s)
                    </p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('prestataire')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Ajouter un prestataire
                    </button>
                </div>

                <div v-if="prestataires.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="p in prestataires" :key="p.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ p.nom }}
                                </h3>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-text-muted">
                                    {{ labelCategoriePrestataire(p.categorie) }}
                                </p>
                            </div>
                            <span v-if="p.note_interne"
                                  class="flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                ★ {{ p.note_interne }}/5
                            </span>
                        </div>

                        <p v-if="p.description" class="mt-3 text-sm text-text-sub line-clamp-2">
                            {{ p.description }}
                        </p>

                        <div class="mt-3 space-y-1 text-xs text-text-sub">
                            <p v-if="p.contact_nom" class="flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ p.contact_nom }}
                            </p>
                            <p v-if="p.email" class="flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ p.email }}
                            </p>
                            <p v-if="p.telephone" class="flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ p.telephone }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-border-soft pt-3">
                            <Link :href="`/prestataires/${p.id}`"
                                  class="text-xs font-bold text-moov-blue hover:underline">
                                Voir les détails →
                            </Link>
                            <button v-if="permissions.peut_supprimer" @click="supprimer('prestataire', p)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun prestataire dans l'annuaire</p>
                    <button v-if="permissions.peut_creer" @click="ouvrirModale('prestataire')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Ajouter le premier prestataire
                    </button>
                </div>
            </div>

            <!-- ═════════ CONTENU ONGLET : BÉNÉVOLES ═════════ -->
            <div v-else-if="ongletActif === 'benevoles'" class="p-5 sm:p-6">

                <div class="mb-4">
                    <p class="text-sm font-bold text-text-sub">
                        {{ postes.length }} poste(s) bénévole(s) au total · {{ kpis.postes_ouverts }} ouvert(s)
                    </p>
                    <p class="mt-1 text-xs text-text-muted">
                        Les postes bénévoles sont créés au sein de chaque événement.
                    </p>
                </div>

                <div v-if="postes.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="poste in postes" :key="poste.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ poste.nom_poste }}
                                </h3>
                                <p class="mt-1 text-xs text-text-sub">
                                    Pour : <strong>{{ poste.evenement?.titre ?? '—' }}</strong>
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                statutPoste(poste).bg, statutPoste(poste).text]">
                                {{ statutPoste(poste).label }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm text-text-sub line-clamp-2">
                            {{ poste.description }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ poste.places_max }} place{{ poste.places_max > 1 ? 's' : '' }}
                            </span>
                            <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                {{ poste.candidatures?.length ?? 0 }} candidature{{ poste.candidatures?.length > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="mt-4 border-t border-border-soft pt-3">
                            <Link :href="`/postes-benevoles/${poste.id}/candidatures`"
                                  class="text-xs font-bold text-moov-blue hover:underline">
                                Voir les candidatures →
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun poste bénévole</p>
                    <p class="mt-1 text-xs text-text-sub">
                        Créez des postes depuis la page logistique d'un événement.
                    </p>
                </div>
            </div>
        </div>

        <!-- ═════════ MODALE : CRÉER UN LIEU ═════════ -->
        <div v-if="modaleOuverte === 'lieu'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Nouveau lieu</h3>
                        <p class="mt-1 text-sm text-text-sub">Ajouter un lieu au catalogue</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="creerLieu" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom *</label>
                        <input v-model="formLieu.nom" type="text" required
                               placeholder="Ex: Salle Conférence A"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Capacité max</label>
                            <input v-model="formLieu.capacite_max" type="text" inputmode="numeric"
                                   @input="formLieu.capacite_max = $event.target.value.replace(/\D/g, '')"
                                   placeholder="Ex: 200"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Ville</label>
                            <input v-model="formLieu.ville" type="text"
                                   placeholder="Ouagadougou"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Adresse</label>
                        <input v-model="formLieu.adresse" type="text"
                               placeholder="Adresse complète"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Description</label>
                        <textarea v-model="formLieu.description" rows="3"
                                  placeholder="Équipements inclus, particularités..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formLieu.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formLieu.processing ? 'Création...' : 'Créer le lieu' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═════════ MODALE : CRÉER DU MATÉRIEL ═════════ -->
        <div v-if="modaleOuverte === 'materiel'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Nouveau matériel</h3>
                        <p class="mt-1 text-sm text-text-sub">Ajouter du matériel au catalogue</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="creerMateriel" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom *</label>
                        <input v-model="formMateriel.nom" type="text" required
                               placeholder="Ex: Vidéoprojecteur Full HD"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Catégorie *</label>
                            <select v-model="formMateriel.categorie" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option value="">Sélectionner</option>
                                <option v-for="(label, code) in categoriesMateriel" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">État *</label>
                            <select v-model="formMateriel.etat" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option v-for="(label, code) in etatsMateriel" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Quantité *</label>
                            <input v-model="formMateriel.quantite_totale" type="text" inputmode="numeric" required
                                   @input="formMateriel.quantite_totale = $event.target.value.replace(/\D/g, '')"
                                   placeholder="Ex: 10"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Unité *</label>
                            <select v-model="formMateriel.unite" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option value="pièce">Pièce</option>
                                <option value="lot">Lot</option>
                                <option value="carton">Carton</option>
                                <option value="paire">Paire</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Stockage</label>
                            <input v-model="formMateriel.lieu_stockage" type="text"
                                   placeholder="Magasin Moov"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Description</label>
                        <textarea v-model="formMateriel.description" rows="3"
                                  placeholder="Détails techniques..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formMateriel.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formMateriel.processing ? 'Création...' : 'Créer le matériel' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═════════ MODALE : CRÉER UN PRESTATAIRE ═════════ -->
        <div v-if="modaleOuverte === 'prestataire'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Nouveau prestataire</h3>
                        <p class="mt-1 text-sm text-text-sub">Ajouter un fournisseur à l'annuaire</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="creerPrestataire" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom de l'entreprise *</label>
                            <input v-model="formPrestataire.nom" type="text" required
                                   placeholder="Ex: AudioVision Burkina"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Catégorie *</label>
                            <select v-model="formPrestataire.categorie" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option value="">Sélectionner</option>
                                <option v-for="(label, code) in categoriesPrestataire" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Personne contact</label>
                            <input v-model="formPrestataire.contact_nom" type="text"
                                   placeholder="Ex: Mariam Sawadogo"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Ville</label>
                            <input v-model="formPrestataire.ville" type="text"
                                   placeholder="Ouagadougou"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Email</label>
                            <input v-model="formPrestataire.email" type="email"
                                   placeholder="contact@entreprise.bf"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Téléphone</label>
                            <input v-model="formPrestataire.telephone" type="text"
                                   placeholder="+226 70 11 22 33"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Description / Services</label>
                        <textarea v-model="formPrestataire.description" rows="3"
                                  placeholder="Services proposés, spécialités..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Note interne (/5)</label>
                        <select v-model="formPrestataire.note_interne"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                            <option value="">Pas de note</option>
                            <option value="1">★ - À éviter</option>
                            <option value="2">★★ - Correct</option>
                            <option value="3">★★★ - Bon</option>
                            <option value="4">★★★★ - Très bon</option>
                            <option value="5">★★★★★ - Excellent</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formPrestataire.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formPrestataire.processing ? 'Création...' : 'Créer le prestataire' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </DashboardLayout>
</template>