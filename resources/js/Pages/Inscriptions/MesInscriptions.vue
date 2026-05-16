<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    inscriptions: { type: Array, required: true },
    filters:      { type: Object, default: () => ({}) },
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}


const couleurStatut = (statut) => ({
    preinscrit:     { dot: 'bg-amber-500',    text: 'text-amber-700',   label: 'Pré-inscrit' },
    preselectionne: { dot: 'bg-blue-500',     text: 'text-blue-700',    label: 'Présélectionné' },
    dossier_soumis: { dot: 'bg-indigo-500',   text: 'text-indigo-700',  label: 'Dossier soumis' },
    en_analyse:     { dot: 'bg-violet-500',   text: 'text-violet-700',  label: 'En analyse' },
    recommandee:    { dot: 'bg-cyan-500',     text: 'text-cyan-700',    label: 'Recommandée' },
    acceptee:       { dot: 'bg-emerald-500',  text: 'text-emerald-700', label: 'Acceptée' },
    confirmee:      { dot: 'bg-emerald-600',  text: 'text-emerald-800', label: 'Confirmée' },
    refusee:        { dot: 'bg-red-500',      text: 'text-red-700',     label: 'Refusée' },
    present:        { dot: 'bg-emerald-600',  text: 'text-emerald-800', label: 'Présent' },
    annulee:        { dot: 'bg-slate-400',    text: 'text-slate-600',   label: 'Annulée' },
}[statut] || { dot: 'bg-slate-400', text: 'text-slate-600', label: statut })

const couleurTypeIndicateur = (code) => ({
    BARA_MOUSSO: 'bg-rose-500',
    CONF:        'bg-blue-500',
    SPORT:       'bg-cyan-500',
    CHALLENGE:   'bg-violet-500',
    FORMATION:   'bg-emerald-500',
    HACK:        'bg-orange-500',
    SALON:       'bg-indigo-500',
}[code] || 'bg-slate-500')

const progression = (statut) => ({
    preinscrit: 15, preselectionne: 30, dossier_soumis: 50,
    en_analyse: 65, recommandee: 80, acceptee: 90,
    confirmee: 100, present: 100, refusee: 0, annulee: 0,
}[statut] || 0)

const stats = computed(() => ({
    total:        props.inscriptions.length,
    a_venir:      props.inscriptions.filter(i => ['confirmee', 'acceptee'].includes(i.statut)).length,
    en_attente:   props.inscriptions.filter(i => ['preinscrit', 'preselectionne', 'dossier_soumis', 'en_analyse', 'recommandee'].includes(i.statut)).length,
    a_completer:  props.inscriptions.filter(i => i.statut === 'preselectionne').length,
}))
</script>

<template>
    <PublicLayout>
        <section class="bg-page-bg min-h-screen py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- En-tête -->
                <div class="mb-10">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-text-muted">
                        Espace personnel
                    </p>
                    <h1 class="mt-2 font-display text-3xl font-extrabold text-text-main">
                        Mes inscriptions
                    </h1>
                    <p class="mt-2 text-sm text-text-sub">
                        Suivez l'évolution de vos candidatures aux événements Moov Africa Burkina
                    </p>
                </div>

               
                <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Total</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">À venir</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ stats.a_venir }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">En attente</p>
                        <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ stats.en_attente }}</p>
                    </div>
                    <div :class="['rounded-xl p-5 shadow-sm transition',
                        stats.a_completer > 0 ? 'bg-moov-noir text-white' : 'bg-white']">
                        <p :class="['text-[11px] font-bold uppercase tracking-wider',
                            stats.a_completer > 0 ? 'text-white/60' : 'text-text-muted']">
                            À compléter
                        </p>
                        <p :class="['mt-3 font-display text-3xl font-extrabold',
                            stats.a_completer > 0 ? 'text-moov-orange' : 'text-text-main']">
                            {{ stats.a_completer }}
                        </p>
                    </div>
                </div>

                
                <div v-if="inscriptions.length > 0" class="space-y-3">
                    <Link v-for="insc in inscriptions"
                          :key="insc.id"
                          :href="`/inscriptions/${insc.id}`"
                          class="group block rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md">

                        <div class="flex flex-wrap items-start justify-between gap-4">

                            <!-- Colonne gauche -->
                            <div class="min-w-0 flex-1">

                                <!-- Type avec petit point coloré -->
                                <div class="mb-2 flex items-center gap-2">
                                    <span :class="['h-2 w-2 rounded-full',
                                        couleurTypeIndicateur(insc.evenement?.type_evenement?.code)]"/>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                        {{ insc.evenement?.type_evenement?.nom ?? '—' }}
                                    </span>
                                </div>

                                <!-- Titre -->
                                <h3 class="font-display text-lg font-extrabold text-text-main transition group-hover:text-moov-blue">
                                    {{ insc.evenement?.titre }}
                                </h3>

                                <!-- Infos -->
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-sub">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ formaterDate(insc.evenement?.date_debut) }}
                                    </span>
                                    <span v-if="insc.evenement?.lieu" class="flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0L6.343 16.657a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ insc.evenement.lieu.nom }}
                                    </span>
                                    <span v-if="insc.qr_code && ['confirmee', 'acceptee', 'present'].includes(insc.statut)"
                                          class="flex items-center gap-1.5 font-mono font-bold text-emerald-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                        {{ insc.qr_code }}
                                    </span>
                                </div>

                                <!-- Barre de progression discrète -->
                                <div v-if="!['refusee', 'annulee'].includes(insc.statut)"
                                     class="mt-4 max-w-md">
                                    <div class="flex items-center gap-3">
                                        <div class="h-1 flex-1 overflow-hidden rounded-full bg-page-bg">
                                            <div class="h-full rounded-full bg-text-main transition-all"
                                                 :style="{ width: progression(insc.statut) + '%' }"/>
                                        </div>
                                        <span class="text-[10px] font-bold tabular-nums text-text-muted">
                                            {{ progression(insc.statut) }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Motif rejet -->
                                <p v-if="insc.statut === 'refusee' && insc.motif_refus"
                                   class="mt-3 text-xs text-text-sub">
                                    <span class="font-bold text-red-700">Motif :</span> {{ insc.motif_refus }}
                                </p>
                            </div>

                            <!-- Colonne droite : statut + action -->
                            <div class="flex flex-col items-end gap-2">
                                <span class="inline-flex items-center gap-2 text-sm font-bold"
                                      :class="couleurStatut(insc.statut).text">
                                    <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(insc.statut).dot]"/>
                                    {{ couleurStatut(insc.statut).label }}
                                </span>

                                <span v-if="insc.statut === 'preselectionne'"
                                      class="rounded-md bg-moov-orange/10 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-moov-orange">
                                    Action requise
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- État vide -->
                <div v-else class="rounded-xl bg-white p-16 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-4 font-display text-base font-extrabold text-text-main">
                        Aucune inscription pour le moment
                    </p>
                    <p class="mt-1 text-sm text-text-sub">
                        Découvrez les événements ouverts aux inscriptions
                    </p>
                    <Link href="/evenements"
                          class="mt-6 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                        Voir les événements →
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>