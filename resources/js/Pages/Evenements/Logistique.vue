<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:          { type: Object, required: true },
    kpis:               { type: Object, required: true },
    materielsDispo:     { type: Array, required: true },
    prestatairesDispo:  { type: Array, required: true },
    categoriesPostes:   { type: Object, required: true },
    permissions:        { type: Object, required: true },
})

// ═════════ ONGLETS ═════════
const ongletActif = ref('lieu')

const onglets = [
    { key: 'lieu',          label: 'Salle & Espace',  icon: 'pin' },
    { key: 'materiel',      label: 'Matériel',         icon: 'screen' },
    { key: 'prestataires',  label: 'Prestataires',     icon: 'truck' },
    { key: 'benevoles',     label: 'Bénévoles',        icon: 'users' },
]

// ═════════ MODALES ═════════
const modaleOuverte = ref(null) // 'materiel' | 'prestataire' | 'poste' | null

const ouvrirModale = (type) => {
    modaleOuverte.value = type
    if (type === 'materiel') formMateriel.reset()
    if (type === 'prestataire') formPrestataire.reset()
    if (type === 'poste') formPoste.reset()
}

const fermerModale = () => {
    modaleOuverte.value = null
}

// ═════════ FORMS ═════════

const formMateriel = useForm({
    materiel_id: '',
    quantite_prevue: '',
    note: '',
})

const formPrestataire = useForm({
    prestataire_id: '',
    prestation: '',
    montant_prevu: '',
    note: '',
})

const formPoste = useForm({
    nom_poste: '',
    categorie: '',
    description: '',
    competences_requises: '',
    places_max: 1,
    horaire_debut: '',
    horaire_fin: '',
})

// ═════════ ACTIONS ═════════

const affecterMateriel = () => {
    formMateriel.post(`/evenements/${props.evenement.id}/logistique/materiel`, {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
    })
}

const detacherMateriel = (materielId) => {
    if (!confirm('Retirer ce matériel de l\'événement ?')) return
    router.delete(`/evenements/${props.evenement.id}/logistique/materiel/${materielId}`, {
        preserveScroll: true,
    })
}

const affecterPrestataire = () => {
    formPrestataire.post(`/evenements/${props.evenement.id}/logistique/prestataire`, {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
        forceFormData: true,
    })
}

const detacherPrestataire = (prestataireId) => {
    if (!confirm('Retirer ce prestataire de l\'événement ?')) return
    router.delete(`/evenements/${props.evenement.id}/logistique/prestataire/${prestataireId}`, {
        preserveScroll: true,
    })
}

const creerPoste = () => {
    formPoste.post(`/evenements/${props.evenement.id}/logistique/poste`, {
        onSuccess: () => fermerModale(),
        preserveScroll: true,
    })
}

const supprimerPoste = (posteId) => {
    if (!confirm('Supprimer ce poste bénévole ?')) return
    router.delete(`/postes-benevoles/${posteId}`, { preserveScroll: true })
}

// ═════════ HELPERS UI ═════════

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const formaterMontant = (m) => {
    if (!m) return '—'
    return Number(m).toLocaleString('fr-FR') + ' FCFA'
}

const couleurStatutMateriel = (statut) => ({
    prevu:    { bg: 'bg-amber-50',    text: 'text-amber-700',   label: 'Prévu' },
    sorti:    { bg: 'bg-blue-50',     text: 'text-blue-700',    label: 'Sorti' },
    retourne: { bg: 'bg-emerald-50',  text: 'text-emerald-700', label: 'Retourné' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })

const couleurStatutPrestataire = (statut) => ({
    devis:    { bg: 'bg-amber-50',    text: 'text-amber-700',   label: 'Devis en cours' },
    confirme: { bg: 'bg-blue-50',     text: 'text-blue-700',    label: 'Confirmé' },
    paye:     { bg: 'bg-emerald-50',  text: 'text-emerald-700', label: 'Payé' },
    annule:   { bg: 'bg-red-50',      text: 'text-red-700',     label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })

const couleurStatutPoste = (statut) => ({
    ouvert:  { bg: 'bg-emerald-50', text: 'text-emerald-700', label: 'Ouvert' },
    ferme:   { bg: 'bg-slate-100',  text: 'text-slate-700',   label: 'Fermé' },
    complet: { bg: 'bg-blue-50',    text: 'text-blue-700',    label: 'Complet' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })

// Matériels non encore affectés (pour la modale)
const materielsDisponiblesAffectation = computed(() => {
    const idsAffectes = props.evenement.materiels.map(m => m.id)
    return props.materielsDispo.filter(m => !idsAffectes.includes(m.id))
})

// Prestataires non encore affectés
const prestatairesDisponiblesAffectation = computed(() => {
    const idsAffectes = props.evenement.prestataires.map(p => p.id)
    return props.prestatairesDispo.filter(p => !idsAffectes.includes(p.id))
})
</script>

<template>
    <DashboardLayout>

        <!-- Retour -->
        <Link :href="`/evenements/${evenement.id}`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à l'événement
        </Link>

        <!-- En-tête -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Module Logistique
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                {{ evenement.titre }}
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Gérez les ressources pour cet événement
            </p>
        </div>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Matériel</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-text-main">{{ kpis.materiel_count }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Prestataires</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-text-main">{{ kpis.prestataire_count }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Postes</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-text-main">{{ kpis.postes_count }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bénévoles</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ kpis.benevoles_acceptes }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Dépenses</p>
                <p class="mt-2 font-display text-lg font-extrabold text-moov-blue">
                    {{ Number(kpis.total_depenses).toLocaleString('fr-FR') }}
                    <span class="text-xs text-text-muted">FCFA</span>
                </p>
            </div>
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

            <!-- ═════════ ONGLET : LIEU ═════════ -->
            <div v-if="ongletActif === 'lieu'" class="p-5 sm:p-6">
                <div v-if="evenement.lieu" class="rounded-xl border border-border-soft bg-white p-6">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Lieu réservé pour cet événement
                            </p>
                            <h3 class="mt-2 font-display text-xl font-extrabold text-text-main">
                                {{ evenement.lieu.nom }}
                            </h3>
                            <p v-if="evenement.lieu.capacite_max" class="mt-1 text-sm text-text-sub">
                                Capacité : {{ evenement.lieu.capacite_max }} places
                            </p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                            Réservé
                        </span>
                    </div>

                    <p v-if="evenement.lieu.description" class="mt-4 text-sm text-text-sub">
                        {{ evenement.lieu.description }}
                    </p>

                    <div v-if="evenement.lieu.adresse || evenement.lieu.ville"
                         class="mt-4 flex items-center gap-1.5 text-sm text-text-sub">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0L6.343 16.657a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ [evenement.lieu.adresse, evenement.lieu.ville].filter(Boolean).join(', ') }}</span>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 border-t border-border-soft pt-4">
                        <Link :href="`/lieux/${evenement.lieu.id}`"
                              class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Voir le lieu
                        </Link>
                        <Link v-if="permissions.peut_modifier"
                              :href="`/evenements/${evenement.id}/edit`"
                              class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                            Changer de lieu (modifier l'événement)
                        </Link>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun lieu défini pour cet événement</p>
                    <Link v-if="permissions.peut_modifier"
                          :href="`/evenements/${evenement.id}/edit`"
                          class="mt-3 inline-block rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        Définir un lieu
                    </Link>
                </div>
            </div>

            <!-- ═════════ ONGLET : MATÉRIEL ═════════ -->
            <div v-else-if="ongletActif === 'materiel'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ evenement.materiels.length }} matériel(s) affecté(s) à cet événement
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('materiel')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Affecter du matériel
                    </button>
                </div>

                <div v-if="evenement.materiels.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="m in evenement.materiels" :key="m.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ m.nom }}
                                </h3>
                                <p class="mt-1 text-xs text-text-sub">{{ m.unite }}</p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                couleurStatutMateriel(m.pivot.statut).bg, couleurStatutMateriel(m.pivot.statut).text]">
                                {{ couleurStatutMateriel(m.pivot.statut).label }}
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                Prévu : {{ m.pivot.quantite_prevue }}
                            </span>
                            <span v-if="m.pivot.quantite_sortie > 0"
                                  class="rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                Sorti : {{ m.pivot.quantite_sortie }}
                            </span>
                            <span v-if="m.pivot.quantite_retournee > 0"
                                  class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                Retourné : {{ m.pivot.quantite_retournee }}
                            </span>
                        </div>

                        <p v-if="m.pivot.note" class="mt-3 text-xs text-text-sub italic">
                            {{ m.pivot.note }}
                        </p>

                        <div class="mt-4 flex items-center justify-end gap-2 border-t border-border-soft pt-3">
                            <button v-if="permissions.peut_modifier" @click="detacherMateriel(m.id)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Retirer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun matériel affecté</p>
                    <p class="mt-1 text-xs text-text-sub">
                        Affectez du matériel du catalogue à cet événement
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('materiel')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Affecter du matériel
                    </button>
                </div>
            </div>

            <!-- ═════════ ONGLET : PRESTATAIRES ═════════ -->
            <div v-else-if="ongletActif === 'prestataires'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ evenement.prestataires.length }} prestataire(s) sur cet événement
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('prestataire')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Affecter un prestataire
                    </button>
                </div>

                <div v-if="evenement.prestataires.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="p in evenement.prestataires" :key="p.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ p.nom }}
                                </h3>
                                <p v-if="p.contact_nom" class="mt-1 text-xs text-text-sub">
                                    Contact : {{ p.contact_nom }}
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                couleurStatutPrestataire(p.pivot.statut).bg, couleurStatutPrestataire(p.pivot.statut).text]">
                                {{ couleurStatutPrestataire(p.pivot.statut).label }}
                            </span>
                        </div>

                        <p class="mt-3 text-sm font-medium text-text-main">
                            {{ p.pivot.prestation }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-if="p.pivot.montant_prevu"
                                  class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                Prévu : {{ formaterMontant(p.pivot.montant_prevu) }}
                            </span>
                            <span v-if="p.pivot.montant_final"
                                  class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                Final : {{ formaterMontant(p.pivot.montant_final) }}
                            </span>
                        </div>

                        <p v-if="p.pivot.note" class="mt-3 text-xs text-text-sub italic">
                            {{ p.pivot.note }}
                        </p>

                        <div class="mt-4 flex items-center justify-end gap-2 border-t border-border-soft pt-3">
                            <button v-if="permissions.peut_modifier" @click="detacherPrestataire(p.id)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Retirer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun prestataire affecté</p>
                    <p class="mt-1 text-xs text-text-sub">
                        Affectez des prestataires de l'annuaire à cet événement
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('prestataire')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Affecter un prestataire
                    </button>
                </div>
            </div>

            <!-- ═════════ ONGLET : BÉNÉVOLES ═════════ -->
            <div v-else-if="ongletActif === 'benevoles'" class="p-5 sm:p-6">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-text-sub">
                        {{ evenement.postes_benevoles.length }} poste(s) bénévole(s) créé(s)
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('poste')"
                            class="rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                        + Créer un poste
                    </button>
                </div>

                <div v-if="evenement.postes_benevoles.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="poste in evenement.postes_benevoles" :key="poste.id"
                         class="rounded-xl border border-border-soft bg-white p-5 transition hover:shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-text-main">
                                    {{ poste.nom_poste }}
                                </h3>
                                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-text-muted">
                                    {{ categoriesPostes[poste.categorie] }}
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold',
                                couleurStatutPoste(poste.statut).bg, couleurStatutPoste(poste.statut).text]">
                                {{ couleurStatutPoste(poste.statut).label }}
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
                                {{ poste.candidatures?.length ?? 0 }} candidat{{ poste.candidatures?.length > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-border-soft pt-3">
                            <Link :href="`/postes-benevoles/${poste.id}/candidatures`"
                                  class="text-xs font-bold text-moov-blue hover:underline">
                                Voir candidatures →
                            </Link>
                            <button v-if="permissions.peut_modifier" @click="supprimerPoste(poste.id)"
                                    class="text-xs text-red-600 transition hover:text-red-700">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-border-soft p-12 text-center">
                    <p class="text-sm font-bold text-text-main">Aucun poste bénévole créé</p>
                    <p class="mt-1 text-xs text-text-sub">
                        Créez des postes pour permettre aux participants de candidater
                    </p>
                    <button v-if="permissions.peut_modifier" @click="ouvrirModale('poste')"
                            class="mt-3 rounded-lg bg-moov-noir px-4 py-2 text-sm font-bold text-white">
                        + Créer le premier poste
                    </button>
                </div>
            </div>
        </div>

        <!-- ═════════ MODALE : AFFECTER MATÉRIEL ═════════ -->
        <div v-if="modaleOuverte === 'materiel'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Affecter du matériel</h3>
                        <p class="mt-1 text-sm text-text-sub">Sélectionner du matériel du catalogue</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="affecterMateriel" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Matériel *</label>
                        <select v-model="formMateriel.materiel_id" required
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner du matériel</option>
                            <option v-for="m in materielsDisponiblesAffectation" :key="m.id" :value="m.id">
                                {{ m.nom }} ({{ m.quantite_disponible }} dispo)
                            </option>
                        </select>
                        <p v-if="materielsDisponiblesAffectation.length === 0" class="mt-1 text-xs text-amber-600">
                            Tout le matériel du catalogue est déjà affecté.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Quantité prévue *</label>
                        <input v-model="formMateriel.quantite_prevue" type="text" inputmode="numeric" required
                               @input="formMateriel.quantite_prevue = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 100"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Note</label>
                        <textarea v-model="formMateriel.note" rows="2"
                                  placeholder="Précisions sur l'utilisation..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formMateriel.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formMateriel.processing ? 'Affectation...' : 'Affecter' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═════════ MODALE : AFFECTER PRESTATAIRE ═════════ -->
        <div v-if="modaleOuverte === 'prestataire'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Affecter un prestataire</h3>
                        <p class="mt-1 text-sm text-text-sub">Choisir dans l'annuaire</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="affecterPrestataire" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Prestataire *</label>
                        <select v-model="formPrestataire.prestataire_id" required
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner un prestataire</option>
                            <option v-for="p in prestatairesDisponiblesAffectation" :key="p.id" :value="p.id">
                                {{ p.nom }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Prestation demandée *</label>
                        <textarea v-model="formPrestataire.prestation" rows="2" required
                                  placeholder="Ex: Installation sono + écran géant"
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Montant prévu (FCFA)</label>
                        <input v-model="formPrestataire.montant_prevu" type="text" inputmode="numeric"
                               @input="formPrestataire.montant_prevu = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 850000"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Note</label>
                        <textarea v-model="formPrestataire.note" rows="2"
                                  placeholder="Précisions..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formPrestataire.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formPrestataire.processing ? 'Affectation...' : 'Affecter' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═════════ MODALE : CRÉER POSTE BÉNÉVOLE ═════════ -->
        <div v-if="modaleOuverte === 'poste'"
             class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto"
             @click.self="fermerModale">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white shadow-2xl">

                <div class="flex items-start justify-between border-b border-border-soft p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">Créer un poste bénévole</h3>
                        <p class="mt-1 text-sm text-text-sub">Les participants pourront candidater</p>
                    </div>
                    <button @click="fermerModale" class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="creerPoste" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom du poste *</label>
                            <input v-model="formPoste.nom_poste" type="text" required
                                   placeholder="Ex: Accueil & orientation"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Catégorie *</label>
                            <select v-model="formPoste.categorie" required
                                    class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option value="">Sélectionner</option>
                                <option v-for="(label, code) in categoriesPostes" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Description *</label>
                        <textarea v-model="formPoste.description" rows="3" required
                                  placeholder="Décrire le rôle, les missions..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Compétences requises</label>
                        <input v-model="formPoste.competences_requises" type="text"
                               placeholder="Ex: Sociable, ponctuel, bilingue"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Places *</label>
                            <input v-model="formPoste.places_max" type="text" inputmode="numeric" required
                                   @input="formPoste.places_max = $event.target.value.replace(/\D/g, '')"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Début</label>
                            <input v-model="formPoste.horaire_debut" type="datetime-local"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Fin</label>
                            <input v-model="formPoste.horaire_fin" type="datetime-local"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border-soft pt-4">
                        <button type="button" @click="fermerModale"
                                class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formPoste.processing"
                                class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formPoste.processing ? 'Création...' : 'Créer le poste' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </DashboardLayout>
</template>