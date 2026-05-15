<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    kpis:               Object,
    evolutionMensuelle: Array,
    performanceTypes:   Array,
    topEvenements:      Array,
    evenements:         Array,
    filters:            Object,
})

const filtreEvenement = ref(props.filters?.evenement_id ?? '')
const filtrePeriode   = ref(props.filters?.periode ?? '12mois')

const filtrer = () => {
    router.get('/rapports', {
        evenement_id: filtreEvenement.value || undefined,
        periode:      filtrePeriode.value,
    }, { preserveState: true, preserveScroll: true })
}

const reinitialiser = () => {
    filtreEvenement.value = ''
    filtrePeriode.value   = '12mois'
    router.get('/rapports')
}

const couleurType = (code) => ({
    BARA_MOUSSO: 'bg-amber-50 text-amber-700',
    CONF:        'bg-rose-50 text-rose-700',
    SPORT:       'bg-blue-50 text-blue-700',
    CHALLENGE:   'bg-violet-50 text-violet-700',
    FORMATION:   'bg-emerald-50 text-emerald-700',
    HACK:        'bg-orange-50 text-orange-700',
    SALON:       'bg-indigo-50 text-indigo-700',
}[code] || 'bg-slate-50 text-slate-700')

const formaterMontant = (m) => Number(m).toLocaleString('fr-FR')

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const maxEvolution = computed(() => {
    if (!props.evolutionMensuelle?.length) return 0
    return Math.max(...props.evolutionMensuelle.map(e => e.total), 1)
})

const maxPerformance = computed(() => {
    if (!props.performanceTypes?.length) return 0
    return Math.max(...props.performanceTypes.map(p => p.nb_inscriptions), 1)
})

const maxBeneficiaires = computed(() => {
    if (!props.topEvenements?.length) return 0
    return Math.max(...props.topEvenements.map(e => e.beneficiaires), 1)
})
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-extrabold text-text-main">
                    Impact RSE & Rapports
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Vue d'ensemble de l'impact social des événements
                </p>
            </div>

            <a :href="`/rapports/export-global?evenement_id=${filtreEvenement || ''}&periode=${filtrePeriode}`"
               class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter en PDF
            </a>
        </div>

        <!-- ── FILTRES ── -->
        <div class="mb-6 rounded-xl bg-card p-4 shadow-card">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Événement</label>
                    <select v-model="filtreEvenement" @change="filtrer"
                            class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue">
                        <option value="">Tous les événements</option>
                        <option v-for="ev in evenements" :key="ev.id" :value="ev.id">{{ ev.titre }}</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">Période</label>
                    <select v-model="filtrePeriode" @change="filtrer"
                            class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue">
                        <option value="3mois">3 derniers mois</option>
                        <option value="6mois">6 derniers mois</option>
                        <option value="12mois">12 derniers mois</option>
                        <option value="all">Toute la période</option>
                    </select>
                </div>
                <button v-if="filtreEvenement || filtrePeriode !== '12mois'"
                        @click="reinitialiser"
                        class="self-end rounded-lg border border-border-soft bg-white px-4 py-2 text-xs font-bold text-text-sub transition hover:bg-page-bg">
                    Réinitialiser
                </button>
            </div>
        </div>

        <!-- ── KPIs IMPACT RSE ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Bénéficiaires directs
                </p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">
                    {{ formaterMontant(kpis.beneficiaires_directs) }}
                </p>
                <p class="mt-1 text-xs text-text-sub">+ {{ formaterMontant(kpis.beneficiaires_indirects) }} indirects</p>
            </div>

            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Femmes bénéficiaires</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-rose-600">
                    {{ kpis.taux_femmes }}<span class="text-base">%</span>
                </p>
                <p class="mt-1 text-xs text-text-sub">{{ formaterMontant(kpis.femmes) }} personnes</p>
            </div>

            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Associations soutenues</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">
                    {{ formaterMontant(kpis.associations) }}
                </p>
                <p class="mt-1 text-xs text-text-sub">{{ formaterMontant(kpis.projets) }} projets</p>
            </div>

            <div class="rounded-xl bg-card p-5 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Emplois créés</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-orange-600">
                    {{ formaterMontant(kpis.emplois) }}
                </p>
                <p class="mt-1 text-xs text-text-sub">Impact économique</p>
            </div>
        </div>

        <!-- ── KPIs FINANCIERS ── -->
        <div class="mb-8 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 text-white shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-white/70">Montants collectés</p>
                <p class="mt-2 font-display text-3xl font-extrabold">
                    {{ formaterMontant(kpis.collectes) }} <span class="text-base">FCFA</span>
                </p>
                <p class="mt-1 text-xs text-white/80">Levée de fonds totale</p>
            </div>

            <div class="rounded-xl bg-gradient-to-br from-moov-blue to-moov-blue-dark p-5 text-white shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-white/70">Retombées partenaires</p>
                <p class="mt-2 font-display text-3xl font-extrabold">
                    {{ formaterMontant(kpis.retombees) }} <span class="text-base">FCFA</span>
                </p>
                <p class="mt-1 text-xs text-white/80">Visibilité commerciale générée</p>
            </div>
        </div>

        <!-- ── ÉVOLUTION MENSUELLE ── -->
        <div class="mb-6 rounded-xl bg-card p-6 shadow-card">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="font-display text-base font-bold text-text-main">
                        Évolution des bénéficiaires
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">12 derniers mois</p>
                </div>
            </div>

            <div v-if="evolutionMensuelle?.length" class="flex h-48 items-end justify-between gap-1">
                <div v-for="(m, i) in evolutionMensuelle" :key="i"
                     class="group flex flex-1 flex-col items-center gap-1">
                    <div class="relative w-full">
                        <div :style="{ height: m.total > 0 ? (m.total / maxEvolution * 160) + 'px' : '4px' }"
                             :class="['w-full rounded-t-lg transition',
                                 m.total > 0 ? 'bg-moov-blue hover:bg-moov-blue-dark' : 'bg-page-bg']">
                        </div>
                        <span class="absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-moov-noir px-2 py-0.5 text-xs font-bold text-white opacity-0 transition group-hover:opacity-100">
                            {{ m.total }}
                        </span>
                    </div>
                    <p class="text-xs font-bold text-text-sub">{{ m.mois }}</p>
                </div>
            </div>

            <div v-else class="py-12 text-center text-sm text-text-sub">
                Aucune donnée d'évolution disponible
            </div>
        </div>

        <!-- ── PERFORMANCE PAR TYPE ── -->
        <div class="mb-6 rounded-xl bg-card p-6 shadow-card">
            <h2 class="mb-1 font-display text-base font-bold text-text-main">
                Performance par type d'événement
            </h2>
            <p class="mb-5 text-xs text-text-sub">Inscriptions générées par catégorie</p>

            <div v-if="performanceTypes?.length" class="space-y-4">
                <div v-for="(p, i) in performanceTypes" :key="i">
                    <div class="mb-1 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span :class="['rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurType(p.code)]">
                                {{ p.type }}
                            </span>
                            <span class="text-xs text-text-sub">{{ p.nb_evenements }} événement(s)</span>
                        </div>
                        <span class="font-display text-lg font-extrabold text-text-main">
                            {{ p.nb_inscriptions }}
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-page-bg">
                        <div class="h-full rounded-full bg-moov-blue transition-all"
                             :style="{ width: (p.nb_inscriptions / maxPerformance * 100) + '%' }"/>
                    </div>
                </div>
            </div>

            <div v-else class="py-10 text-center text-sm text-text-sub">
                Aucun événement à analyser
            </div>
        </div>

        <!-- ── TOP ÉVÉNEMENTS PAR IMPACT ── -->
        <div class="rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft p-5">
                <h2 class="font-display text-base font-bold text-text-main">
                    Top 10 événements par impact
                </h2>
                <p class="mt-1 text-xs text-text-sub">Classement par nombre de bénéficiaires</p>
            </div>

            <div v-if="topEvenements?.length" class="divide-y divide-border-soft">
                <Link v-for="(ev, i) in topEvenements" :key="ev.id"
                      :href="`/evenements/${ev.id}`"
                      class="flex items-center gap-4 p-4 transition hover:bg-page-bg/50">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full font-display text-sm font-extrabold"
                          :class="i === 0 ? 'bg-amber-100 text-amber-700' :
                                  i === 1 ? 'bg-slate-100 text-slate-700' :
                                  i === 2 ? 'bg-orange-100 text-orange-700' :
                                  'bg-page-bg text-text-sub'">
                        {{ i + 1 }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-text-main">{{ ev.titre }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-text-sub">
                            <span :class="['rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurType(ev.type_code)]">
                                {{ ev.type }}
                            </span>
                            <span>{{ formaterDate(ev.date_debut) }}</span>
                            <span>·</span>
                            <span>{{ ev.inscriptions_count }} inscrits</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-display text-2xl font-extrabold text-moov-blue">
                            {{ formaterMontant(ev.beneficiaires) }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                            Bénéficiaires
                        </p>
                    </div>
                </Link>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun événement avec données RSE</p>
                <p class="mt-1 text-sm text-text-sub">Renseignez les objectifs RSE de vos événements</p>
            </div>
        </div>

    </DashboardLayout>
</template>