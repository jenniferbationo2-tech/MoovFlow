<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:          { type: Object, required: true },
    enquete:            { type: Object, required: true },
    kpis:               { type: Object, default: () => ({}) },
    analyseParQuestion: { type: Array, default: () => [] },
})

// ─── HELPERS ──────────────────────────
const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50',    text: 'text-amber-700',    label: 'Brouillon' },
    publie:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  label: 'Publiée' },
    cloture:   { bg: 'bg-slate-100',   text: 'text-slate-600',    label: 'Clôturée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })

const labelTypeQuestion = (type) => ({
    note:           'Note',
    choix_unique:   'Choix unique',
    choix_multiple: 'Choix multiples',
    texte_court:    'Texte court',
    texte_long:     'Texte long',
    oui_non:        'Oui/Non',
}[type] || type)

// ─── COULEURS DES OPTIONS ─────────────
const couleursOptions = [
    'bg-moov-blue',
    'bg-moov-orange',
    'bg-emerald-500',
    'bg-purple-500',
    'bg-pink-500',
    'bg-amber-500',
    'bg-red-500',
    'bg-teal-500',
]

const couleurOption = (index) => couleursOptions[index % couleursOptions.length]

// ─── CALCULS POUR DISTRIBUTION ────────
const maxDistribution = (distribution) => {
    if (!distribution) return 0
    return Math.max(...Object.values(distribution))
}

const pourcentage = (valeur, total) => {
    if (!total || total === 0) return 0
    return Math.round((valeur / total) * 100)
}

const totalReponsesQuestion = (analyse) => {
    if (!analyse.distribution) return analyse.nb_reponses ?? 0
    return Object.values(analyse.distribution).reduce((sum, v) => sum + v, 0)
}
</script>

<template>
    <DashboardLayout>

        <Link :href="`/rapports/evenements/${evenement.id}/satisfaction`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← Retour aux analyses
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                        {{ enquete.type || 'enquête' }}
                    </span>
                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                        couleurStatut(enquete.statut).bg, couleurStatut(enquete.statut).text]">
                        {{ couleurStatut(enquete.statut).label }}
                    </span>
                </div>
                <h1 class="mt-2 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    {{ enquete.titre }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Événement : {{ evenement.titre }}
                </p>
            </div>
            <button @click="window.print()"
                    class="rounded-lg border-2 border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:border-moov-blue hover:text-moov-blue">
                 Imprimer
            </button>
        </div>

        <!-- ─── KPIs ─── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <!-- Note globale -->
            <div class="rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 p-5 text-white shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-white/80">Note globale</p>
                <div class="mt-2 flex items-baseline gap-1">
                    <p class="font-display text-4xl font-extrabold">{{ kpis.note_globale ?? '—' }}</p>
                    <span v-if="kpis.note_globale" class="text-sm font-bold opacity-80">/5</span>
                </div>
                <div v-if="kpis.note_globale" class="mt-1 flex gap-0.5 text-lg">
                    <span v-for="n in 5" :key="n"
                          :class="kpis.note_globale >= n ? 'text-white' : 'text-white/30'">
                        
                    </span>
                </div>
            </div>

            <!-- Total réponses -->
            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Réponses reçues</p>
                <p class="mt-2 font-display text-4xl font-extrabold text-moov-blue">{{ kpis.total_reponses ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">Total des participants</p>
            </div>

            <!-- Taux de réponse -->
            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Taux de réponse</p>
                <p class="mt-2 font-display text-4xl font-extrabold text-emerald-600">{{ kpis.taux_reponse ?? 0 }}%</p>
                <p class="mt-1 text-xs text-text-sub">{{ kpis.total_reponses }} / {{ kpis.inscriptions_total }} inscrits</p>
            </div>

            <!-- Questions -->
            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Questions</p>
                <p class="mt-2 font-display text-4xl font-extrabold text-purple-600">{{ kpis.nb_questions ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">Dans cette enquête</p>
            </div>
        </div>

        <!-- ─── ANALYSE PAR QUESTION ─── -->
        <div class="space-y-4">

            <div v-for="(analyse, qIndex) in analyseParQuestion" :key="analyse.id"
                 class="rounded-xl bg-card p-6 shadow-card">

                <!-- En-tête question -->
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-sm font-extrabold text-white">
                        {{ qIndex + 1 }}
                    </span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">
                                {{ labelTypeQuestion(analyse.type) }}
                            </span>
                            <span v-if="analyse.obligatoire"
                                  class="inline-block rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700">
                                Obligatoire
                            </span>
                        </div>
                        <h3 class="mt-1 font-display text-base font-extrabold text-text-main">
                            {{ analyse.label }}
                        </h3>
                        <p class="mt-1 text-xs text-text-sub">
                            <strong>{{ analyse.nb_reponses }}</strong> réponse(s)
                        </p>
                    </div>
                </div>

                <!-- ═════ AFFICHAGE PAR TYPE ═════ -->

                <!-- TYPE : NOTE (étoiles avec distribution) -->
                <div v-if="analyse.type === 'note'" class="space-y-4">

                    <!-- Note moyenne en GROS -->
                    <div class="rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-5 text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Note moyenne</p>
                        <p class="mt-2 font-display text-5xl font-extrabold text-amber-600">
                            {{ analyse.note_moyenne ?? '—' }}<span class="text-2xl">/{{ analyse.echelle }}</span>
                        </p>
                        <div class="mt-2 flex justify-center gap-1 text-2xl">
                            <span v-for="n in analyse.echelle" :key="n"
                                  :class="analyse.note_moyenne >= n ? 'text-amber-400' : 'text-slate-300'">
                                
                            </span>
                        </div>
                    </div>

                    <!-- Distribution par note -->
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wider text-text-sub">
                            Distribution
                        </p>
                        <div class="space-y-2">
                            <div v-for="(count, note) in analyse.distribution" :key="note"
                                 class="flex items-center gap-3">
                                <div class="flex w-16 items-center gap-1">
                                    <span class="text-sm font-bold text-text-main">{{ note }}</span>
                                    <span class="text-amber-400">★</span>
                                </div>
                                <div class="flex-1">
                                    <div class="h-6 overflow-hidden rounded-lg bg-page-bg">
                                        <div class="h-full rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 transition-all"
                                             :style="{ width: pourcentage(count, totalReponsesQuestion(analyse)) + '%' }"/>
                                    </div>
                                </div>
                                <div class="flex w-20 items-baseline justify-end gap-1">
                                    <span class="text-sm font-bold text-text-main">{{ count }}</span>
                                    <span class="text-xs text-text-sub">({{ pourcentage(count, totalReponsesQuestion(analyse)) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TYPE : CHOIX UNIQUE / MULTIPLE -->
                <div v-else-if="['choix_unique', 'choix_multiple'].includes(analyse.type)" class="space-y-2">
                    <div v-for="(count, option, idx) in analyse.distribution" :key="option"
                         class="flex items-center gap-3">
                        <div class="w-48 truncate text-sm font-medium text-text-main">
                            {{ option }}
                        </div>
                        <div class="flex-1">
                            <div class="h-7 overflow-hidden rounded-lg bg-page-bg">
                                <div :class="[couleurOption(idx), 'h-full rounded-lg transition-all flex items-center justify-end pr-2']"
                                     :style="{ width: pourcentage(count, totalReponsesQuestion(analyse)) + '%' }">
                                    <span v-if="pourcentage(count, totalReponsesQuestion(analyse)) > 10"
                                          class="text-xs font-bold text-white">
                                        {{ pourcentage(count, totalReponsesQuestion(analyse)) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="w-12 text-right text-sm font-bold text-text-main">
                            {{ count }}
                        </div>
                    </div>
                </div>

                <!-- TYPE : OUI/NON (2 grosses cards) -->
                <div v-else-if="analyse.type === 'oui_non'" class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 p-5 text-center text-white shadow-card">
                        <p class="text-3xl">✓</p>
                        <p class="mt-1 font-display text-4xl font-extrabold">
                            {{ analyse.distribution?.oui ?? 0 }}
                        </p>
                        <p class="text-xs font-bold uppercase tracking-wider text-white/80">Oui</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ analyse.taux_oui ?? 0 }}%</p>
                    </div>
                    <div class="rounded-xl bg-gradient-to-br from-red-400 to-red-600 p-5 text-center text-white shadow-card">
                        <p class="text-3xl">✕</p>
                        <p class="mt-1 font-display text-4xl font-extrabold">
                            {{ analyse.distribution?.non ?? 0 }}
                        </p>
                        <p class="text-xs font-bold uppercase tracking-wider text-white/80">Non</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ 100 - (analyse.taux_oui ?? 0) }}%</p>
                    </div>
                </div>

                <!-- TYPE : TEXTE (verbatims) -->
                <div v-else-if="['texte_long', 'texte_court'].includes(analyse.type)">
                    <div v-if="analyse.verbatims?.length === 0" class="rounded-lg bg-page-bg/50 p-4 text-center text-sm text-text-sub italic">
                        Aucune réponse textuelle
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(verbatim, vi) in analyse.verbatims" :key="vi"
                             class="rounded-lg border-l-4 border-moov-blue bg-page-bg/30 p-3">
                            <p class="text-sm italic text-text-main">"{{ verbatim }}"</p>
                        </div>
                        <p v-if="analyse.verbatims.length >= 20" class="text-center text-xs italic text-text-muted">
                            (20 premiers verbatims affichés)
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ─── PIED DE PAGE ─── -->
        <div class="mt-6 rounded-xl bg-page-bg/50 p-4 text-center text-xs text-text-muted">
            Analyse générée le {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
            · MoovFlow
        </div>

    </DashboardLayout>
</template>

<style>
@media print {
    nav, aside, header { display: none !important; }
    main { padding: 0 !important; margin: 0 !important; }
    .shadow-card { box-shadow: none !important; border: 1px solid #e5e7eb; }
}
</style>