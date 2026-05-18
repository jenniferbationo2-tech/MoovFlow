<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    candidatures: { type: Array, required: true },
    stats:        { type: Object, required: true },
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const couleurStatut = (statut) => ({
    candidat: { bg: 'bg-amber-100',    text: 'text-amber-700',   label: 'En attente' },
    accepte:  { bg: 'bg-emerald-100',  text: 'text-emerald-700', label: 'Acceptée' },
    refuse:   { bg: 'bg-red-100',      text: 'text-red-700',     label: 'Refusée' },
    annule:   { bg: 'bg-slate-100',    text: 'text-slate-600',   label: 'Annulée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', label: statut })

const annuler = (candidature) => {
    if (!confirm('Annuler votre candidature pour ce poste ?')) return
    router.post(`/candidatures-benevoles/${candidature.id}/annuler`, {}, { preserveScroll: true })
}
</script>

<template>
    <PublicLayout>
        <section class="bg-page-bg py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- En-tête -->
                <div class="mb-8">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-text-muted">
                        Bénévolat
                    </p>
                    <h1 class="mt-2 font-display text-3xl font-extrabold text-text-main">
                        Mes candidatures bénévoles
                    </h1>
                    <p class="mt-2 text-sm text-text-sub">
                        Suivez vos candidatures aux postes bénévoles des événements Moov
                    </p>
                </div>

                <!-- KPIs -->
                <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl bg-white p-5 shadow-card">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Total</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-card">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">En attente</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-amber-600">{{ stats.en_attente }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-card">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Acceptées</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-emerald-600">{{ stats.acceptees }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-card">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Refusées</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-red-600">{{ stats.refusees }}</p>
                    </div>
                </div>

                <!-- Liste -->
                <div v-if="candidatures.length > 0" class="space-y-3">
                    <div v-for="c in candidatures" :key="c.id"
                         class="rounded-xl bg-white p-6 shadow-card">

                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                    {{ c.poste?.evenement?.type_evenement?.nom ?? '—' }}
                                </p>
                                <h3 class="mt-1 font-display text-lg font-extrabold text-text-main">
                                    {{ c.poste?.nom_poste ?? 'Poste supprimé' }}
                                </h3>
                                <p class="mt-1 text-sm text-text-sub">
                                    Pour : <strong>{{ c.poste?.evenement?.titre ?? '—' }}</strong>
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-sub">
                                    <span>Postulée le {{ formaterDate(c.created_at) }}</span>
                                    <span v-if="c.poste?.evenement?.lieu">
                                         {{ c.poste.evenement.lieu.nom }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <span :class="['rounded-full px-3 py-1 text-xs font-bold',
                                    couleurStatut(c.statut).bg, couleurStatut(c.statut).text]">
                                    {{ couleurStatut(c.statut).label }}
                                </span>
                            </div>
                        </div>

                        <!-- Motif refus -->
                        <div v-if="c.statut === 'refuse' && c.motif_refus"
                             class="mt-4 rounded-lg bg-red-50 border-l-4 border-red-500 p-3">
                            <p class="text-xs font-bold text-red-700">Motif du refus :</p>
                            <p class="mt-1 text-xs text-red-800">{{ c.motif_refus }}</p>
                        </div>

                        <!-- Confirmation acceptée -->
                        <div v-if="c.statut === 'accepte'"
                             class="mt-4 rounded-lg bg-emerald-50 border-l-4 border-emerald-500 p-3">
                            <p class="text-xs font-bold text-emerald-700">Vous êtes accepté(e) !</p>
                            <p class="mt-1 text-xs text-emerald-800">
                                Présentez-vous le jour de l'événement pour assurer votre mission de bénévole.
                            </p>
                        </div>

                        <!-- Bouton annuler -->
                        <div v-if="['candidat', 'accepte'].includes(c.statut)"
                             class="mt-4 border-t border-border-soft pt-3 flex justify-end">
                            <button @click="annuler(c)"
                                    class="text-xs font-bold text-red-600 transition hover:text-red-700">
                                Annuler ma candidature
                            </button>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="rounded-xl bg-white p-16 text-center shadow-card">
                    <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="mt-4 font-display text-base font-extrabold text-text-main">
                        Aucune candidature bénévole
                    </p>
                    <p class="mt-1 text-sm text-text-sub">
                        Découvrez les événements ouverts au bénévolat
                    </p>
                    <Link href="/evenements"
                          class="mt-6 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                        Voir les événements 
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>