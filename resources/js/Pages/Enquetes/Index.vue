<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: { type: Object, required: true },
    enquetes:  { type: Array, default: () => [] },
})

// ─── HELPERS ────────────────────────
const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-500',    label: 'Brouillon' },
    publie:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  dot: 'bg-emerald-500',  label: 'Publiée' },
    cloture:   { bg: 'bg-slate-100',   text: 'text-slate-600',    dot: 'bg-slate-400',    label: 'Clôturée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statut })

const labelType = (type) => ({
    satisfaction: 'Satisfaction',
    evaluation:   'Évaluation',
    feedback:     'Feedback',
}[type] || type || 'Enquête')

// ─── ACTIONS ────────────────────────
const publier = (enquete) => {
    if (!confirm(`Publier l'enquête "${enquete.titre}" ? Les participants pourront y répondre immédiatement.`)) return
    router.patch(`/evenements/${props.evenement.id}/communication/enquetes/${enquete.id}/publish`, {}, {
        preserveScroll: true,
    })
}

const cloturer = (enquete) => {
    if (!confirm(`Clôturer l'enquête "${enquete.titre}" ? Aucune nouvelle réponse ne sera acceptée.`)) return
    router.patch(`/evenements/${props.evenement.id}/communication/enquetes/${enquete.id}/close`, {}, {
        preserveScroll: true,
    })
}

// ─── STATS HEADER ───────────────────
const totalReponses = computed(() =>
    props.enquetes.reduce((sum, e) => sum + (e.nb_reponses ?? 0), 0)
)

const nbPubliees = computed(() =>
    props.enquetes.filter(e => e.statut === 'publie').length
)
</script>

<template>
    <DashboardLayout>

        <!-- ─── RETOUR ─── -->
        <Link :href="`/evenements/${evenement.id}`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← Retour à l'événement
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Communication · Enquêtes
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    Enquêtes de l'événement
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                </p>
            </div>

            <Link :href="`/evenements/${evenement.id}/communication/enquetes/create`"
                  class="inline-flex items-center gap-2 rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Créer une enquête
            </Link>
        </div>

        <!-- ─── KPIs RAPIDES ─── -->
        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total enquêtes</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ enquetes.length }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Enquêtes publiées</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ nbPubliees }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total réponses</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-orange">{{ totalReponses }}</p>
            </div>
        </div>

        <!-- ─── LISTE DES ENQUÊTES ─── -->
        <div v-if="enquetes.length === 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Aucune enquête créée</h3>
            <p class="mt-1 text-sm text-text-sub">
                Créez votre première enquête pour recueillir l'avis des participants.
            </p>
            <Link :href="`/evenements/${evenement.id}/communication/enquetes/create`"
                  class="mt-4 inline-block rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-blue-700">
                Créer ma première enquête
            </Link>
        </div>

        <div v-else class="space-y-3">

            <div v-for="enquete in enquetes" :key="enquete.id"
                 class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-lg">

                <div class="flex flex-wrap items-start justify-between gap-4">

                    <!-- Infos enquête -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                {{ labelType(enquete.type) }}
                            </span>
                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                couleurStatut(enquete.statut).bg, couleurStatut(enquete.statut).text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(enquete.statut).dot]"/>
                                {{ couleurStatut(enquete.statut).label }}
                            </span>
                        </div>

                        <Link :href="`/evenements/${evenement.id}/communication/enquetes/${enquete.id}`"
                              class="mt-2 block">
                            <h3 class="font-display text-lg font-extrabold text-text-main hover:text-moov-blue">
                                {{ enquete.titre }}
                            </h3>
                        </Link>

                        <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-text-sub">
                            <span class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <strong>{{ enquete.nb_questions }}</strong> question{{ enquete.nb_questions > 1 ? 's' : '' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <strong class="text-moov-orange">{{ enquete.nb_reponses }}</strong> réponse{{ enquete.nb_reponses > 1 ? 's' : '' }}
                            </span>
                            <span class="text-text-muted">
                                Créée le {{ formatDate(enquete.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-2">

                        <Link :href="`/evenements/${evenement.id}/communication/enquetes/${enquete.id}`"
                              class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-sub transition hover:border-moov-blue hover:text-moov-blue">
                            Voir
                        </Link>

                        <Link v-if="enquete.nb_reponses > 0"
                              :href="`/rapports/enquetes/${enquete.id}/analyse`"
                              class="rounded-lg bg-moov-blue/10 border border-moov-blue/30 px-3 py-1.5 text-xs font-bold text-moov-blue transition hover:bg-moov-blue hover:text-white">
                             Analyser
                        </Link>

                        <button v-if="enquete.statut === 'brouillon'"
                                @click="publier(enquete)"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-emerald-700">
                            Publier
                        </button>

                        <button v-if="enquete.statut === 'publie'"
                                @click="cloturer(enquete)"
                                class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-amber-700">
                            Clôturer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>