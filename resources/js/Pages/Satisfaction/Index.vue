<script setup>
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: { type: Object, required: true },
    enquetes:  { type: Array, default: () => [] },
})

const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50',    text: 'text-amber-700',    label: 'Brouillon' },
    publie:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  label: 'Publiée' },
    cloture:   { bg: 'bg-slate-100',   text: 'text-slate-600',    label: 'Clôturée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const enquetesAvecReponses = props.enquetes.filter(e => e.nb_reponses > 0)
const enquetesSansReponse = props.enquetes.filter(e => e.nb_reponses === 0)
</script>

<template>
    <DashboardLayout>

        <Link :href="`/evenements/${evenement.id}`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← Retour à l'événement
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Rapports · Satisfaction
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Analyse de satisfaction
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                {{ evenement.titre }}
            </p>
        </div>

        <!-- ─── KPIs ─── -->
        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total enquêtes</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ enquetes.length }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Avec réponses</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ enquetesAvecReponses.length }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total réponses</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-orange">
                    {{ enquetes.reduce((sum, e) => sum + (e.nb_reponses ?? 0), 0) }}
                </p>
            </div>
        </div>

        <!-- ─── ENQUÊTES AVEC RÉPONSES ─── -->
        <div v-if="enquetesAvecReponses.length > 0" class="mb-6">
            <h2 class="mb-3 font-display text-base font-extrabold text-text-main">
                Enquêtes avec réponses
            </h2>
            <div class="space-y-3">
                <Link v-for="enquete in enquetesAvecReponses" :key="enquete.id"
                      :href="`/rapports/enquetes/${enquete.id}/analyse`"
                      class="block rounded-xl bg-card p-5 shadow-card transition hover:shadow-lg hover:border-moov-blue/30 border border-transparent">

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                    {{ enquete.type || 'enquête' }}
                                </span>
                                <span :class="['inline-block rounded-full px-2.5 py-1 text-xs font-bold',
                                    couleurStatut(enquete.statut).bg, couleurStatut(enquete.statut).text]">
                                    {{ couleurStatut(enquete.statut).label }}
                                </span>
                            </div>
                            <h3 class="mt-2 font-display text-lg font-extrabold text-text-main">
                                {{ enquete.titre }}
                            </h3>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                                <span><strong>{{ enquete.nb_questions }}</strong> question(s)</span>
                                <span>·</span>
                                <span class="text-moov-orange font-bold">{{ enquete.nb_reponses }} réponse(s)</span>
                                <span>·</span>
                                <span>Créée le {{ formatDate(enquete.created_at) }}</span>
                            </div>
                        </div>
                        <div class="rounded-lg bg-moov-blue px-4 py-2 text-sm font-bold text-white">
                            📊 Voir l'analyse →
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- ─── ENQUÊTES SANS RÉPONSE ─── -->
        <div v-if="enquetesSansReponse.length > 0">
            <h2 class="mb-3 font-display text-base font-extrabold text-text-main">
                Enquêtes sans réponse
            </h2>
            <div class="space-y-3">
                <div v-for="enquete in enquetesSansReponse" :key="enquete.id"
                     class="rounded-xl bg-page-bg/50 p-4 border border-border-soft">

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-text-sub">{{ enquete.titre }}</h3>
                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ enquete.nb_questions }} question(s) · Aucune réponse pour le moment
                            </p>
                        </div>
                        <span :class="['inline-block rounded-full px-2.5 py-1 text-xs font-bold',
                            couleurStatut(enquete.statut).bg, couleurStatut(enquete.statut).text]">
                            {{ couleurStatut(enquete.statut).label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── VIDE ─── -->
        <div v-if="enquetes.length === 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Aucune enquête</h3>
            <p class="mt-1 text-sm text-text-sub">
                Créez d'abord une enquête depuis la page Communication.
            </p>
        </div>

    </DashboardLayout>
</template>