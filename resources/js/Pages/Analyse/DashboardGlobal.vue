<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import LineChart from '@/Components/Charts/LineChart.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'

const props = defineProps({
    kpis:               { type: Object, required: true },
    repartitionType:    { type: Array, required: true },
    evolutionMensuelle: { type: Array, required: true },
    topEvenements:      { type: Array, required: true },
    filters:            { type: Object, default: () => ({}) },
})

// ─── FILTRES PÉRIODE ───
const debut = ref(props.filters.debut)
const fin = ref(props.filters.fin)

const appliquerFiltre = () => {
    router.get('/analyses', { debut: debut.value, fin: fin.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const reinitialiser = () => {
    debut.value = ''
    fin.value = ''
    router.get('/analyses', {}, { preserveScroll: true })
}

// ─── DONNÉES POUR GRAPHIQUE ÉVOLUTION (Ligne) ───
const dataEvolution = computed(() => ({
    labels: props.evolutionMensuelle.map(m => m.mois_label),
    datasets: [
        {
            label: 'Événements',
            data: props.evolutionMensuelle.map(m => m.evenements),
            borderColor: '#1B4A8B',
            backgroundColor: 'rgba(27, 74, 139, 0.1)',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Inscriptions',
            data: props.evolutionMensuelle.map(m => m.inscriptions),
            borderColor: '#FF8000',
            backgroundColor: 'rgba(255, 128, 0, 0.1)',
            fill: true,
            tension: 0.4,
        },
    ],
}))

// ─── DONNÉES POUR GRAPHIQUE TYPE (Camembert) ───
const couleursTypes = {
    BARA_MOUSSO: '#F43F5E',
    CONF:        '#3B82F6',
    SPORT:       '#06B6D4',
    CHALLENGE:   '#8B5CF6',
    FORMATION:   '#10B981',
    HACK:        '#F97316',
    SALON:       '#6366F1',
}

const dataTypes = computed(() => ({
    labels: props.repartitionType.map(t => t.type_nom),
    datasets: [{
        data: props.repartitionType.map(t => t.nombre),
        backgroundColor: props.repartitionType.map(t => couleursTypes[t.type_code] || '#94A3B8'),
        borderWidth: 0,
    }],
}))

// ─── DONNÉES POUR TOP ÉVÉNEMENTS (Barres) ───
const dataTop = computed(() => ({
    labels: props.topEvenements.map(e =>
        e.titre.length > 25 ? e.titre.substring(0, 22) + '...' : e.titre
    ),
    datasets: [{
        label: 'Inscriptions',
        data: props.topEvenements.map(e => e.inscriptions),
        backgroundColor: '#1B4A8B',
        borderRadius: 6,
    }],
}))

// ─── HELPERS ───
const formaterNombre = (n) => Number(n).toLocaleString('fr-FR')
const formaterMontant = (m) => Number(m).toLocaleString('fr-FR') + ' FCFA'
</script>

<template>
    <DashboardLayout>

        <!-- En-tête -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Module Analyse
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    Tableau de bord global
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Vue d'ensemble des performances de Moov Africa Burkina
                </p>
            </div>

            <Link href="/rapport-rse"
                  class="rounded-lg bg-moov-noir px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                Générer rapport RSE →
            </Link>
        </div>

        <!-- Filtre période -->
        <div class="mb-6 rounded-xl bg-white p-5 shadow-card">
            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-text-sub">
                Période d'analyse
            </p>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-text-muted">Du</label>
                    <input v-model="debut" type="date"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-text-muted">Au</label>
                    <input v-model="fin" type="date"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="appliquerFiltre"
                            class="flex-1 rounded-lg bg-moov-blue px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-700">
                        Appliquer
                    </button>
                    <button @click="reinitialiser"
                            class="rounded-lg border-2 border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- ═════════ KPIs PRINCIPAUX ═════════ -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">

            <!-- KPI 1 : Événements -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Événements
                    </p>
                    <div class="rounded-lg bg-blue-50 p-2">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ kpis.total_evenements }}</p>
                <p class="mt-1 text-xs text-text-sub">
                    <span class="font-bold text-emerald-600">{{ kpis.evenements_publies }}</span> publiés ·
                    <span class="font-bold text-text-muted">{{ kpis.evenements_termines }}</span> terminés
                </p>
            </div>

            <!-- KPI 2 : Inscriptions -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Inscriptions
                    </p>
                    <div class="rounded-lg bg-orange-50 p-2">
                        <svg class="h-5 w-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ formaterNombre(kpis.total_inscriptions) }}</p>
                <p class="mt-1 text-xs text-text-sub">
                    <span class="font-bold text-emerald-600">{{ kpis.taux_acceptation }}%</span> acceptées
                </p>
            </div>

            <!-- KPI 3 : Présence -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Taux de présence
                    </p>
                    <div class="rounded-lg bg-emerald-50 p-2">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-emerald-600">{{ kpis.taux_presence }}%</p>
                <p class="mt-1 text-xs text-text-sub">
                    {{ formaterNombre(kpis.total_present) }} présents
                </p>
            </div>

            <!-- KPI 4 : Budget -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Budget total
                    </p>
                    <div class="rounded-lg bg-violet-50 p-2">
                        <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-xl font-extrabold text-text-main">
                    {{ formaterNombre(kpis.budget_total) }}
                    <span class="text-xs text-text-muted">FCFA</span>
                </p>
                <p class="mt-1 text-xs text-text-sub">Tous événements confondus</p>
            </div>

            <!-- KPI 5 : Bénéficiaires RSE -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Bénéficiaires RSE
                    </p>
                    <div class="rounded-lg bg-rose-50 p-2">
                        <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-rose-600">{{ formaterNombre(kpis.cible_beneficiaires) }}</p>
                <p class="mt-1 text-xs text-text-sub">Cible totale visée</p>
            </div>

            <!-- KPI 6 : Bénévoles -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Bénévoles
                    </p>
                    <div class="rounded-lg bg-cyan-50 p-2">
                        <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ kpis.benevoles_mobilises }}</p>
                <p class="mt-1 text-xs text-text-sub">
                    sur {{ kpis.total_postes }} postes
                </p>
            </div>

            <!-- KPI 7 : Présents -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Acceptées
                    </p>
                    <div class="rounded-lg bg-amber-50 p-2">
                        <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-text-main">{{ formaterNombre(kpis.total_acceptes) }}</p>
                <p class="mt-1 text-xs text-text-sub">Candidatures validées</p>
            </div>

            <!-- KPI 8 : Taux engagement -->
            <div class="rounded-xl bg-gradient-to-br from-moov-blue to-blue-700 p-5 text-white shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-white/80">
                        Engagement
                    </p>
                    <div class="rounded-lg bg-white/20 p-2">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold">
                    {{ kpis.total_evenements > 0 ? Math.round(kpis.total_inscriptions / kpis.total_evenements) : 0 }}
                </p>
                <p class="mt-1 text-xs text-white/80">Inscriptions / événement</p>
            </div>
        </div>

        <!-- ═════════ GRAPHIQUES ═════════ -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Évolution mensuelle (2/3 largeur) -->
            <div class="rounded-xl bg-white p-5 shadow-card lg:col-span-2">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Évolution mensuelle
                    </h3>
                    <p class="text-xs text-text-sub">12 derniers mois</p>
                </div>
                <div class="h-72">
                    <LineChart :chart-data="dataEvolution"/>
                </div>
            </div>

            <!-- Répartition par type (1/3 largeur) -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Par type d'événement
                    </h3>
                    <p class="text-xs text-text-sub">{{ repartitionType.length }} types</p>
                </div>
                <div v-if="repartitionType.length > 0" class="h-72">
                    <DoughnutChart :chart-data="dataTypes"/>
                </div>
                <div v-else class="flex h-72 items-center justify-center">
                    <p class="text-sm text-text-muted">Aucune donnée</p>
                </div>
            </div>
        </div>

        <!-- Top événements + Détails par type -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Top 5 événements -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Top 5 événements
                    </h3>
                    <p class="text-xs text-text-sub">Classement par inscriptions</p>
                </div>
                <div v-if="topEvenements.length > 0" class="h-72">
                    <BarChart :chart-data="dataTop"/>
                </div>
                <div v-else class="flex h-72 items-center justify-center">
                    <p class="text-sm text-text-muted">Aucune donnée</p>
                </div>
            </div>

            <!-- Liste détaillée par type -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Détail par type
                    </h3>
                    <p class="text-xs text-text-sub">Nombre d'événements organisés</p>
                </div>
                <div v-if="repartitionType.length > 0" class="space-y-3">
                    <div v-for="t in repartitionType" :key="t.type_id"
                         class="flex items-center justify-between rounded-lg border border-border-soft p-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full"
                                  :style="{ backgroundColor: couleursTypes[t.type_code] || '#94A3B8' }"/>
                            <span class="text-sm font-bold text-text-main">{{ t.type_nom }}</span>
                        </div>
                        <span class="rounded-md bg-page-bg px-2.5 py-1 text-sm font-extrabold text-moov-blue">
                            {{ t.nombre }}
                        </span>
                    </div>
                </div>
                <div v-else class="flex h-32 items-center justify-center">
                    <p class="text-sm text-text-muted">Aucun événement sur la période</p>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>