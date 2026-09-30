<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'

const props = defineProps({
    kpis:            { type: Object, required: true },
    repartitionType: { type: Array, required: true },
    evenements:      { type: Array, required: true },
    filters:         { type: Object, default: () => ({}) },
    periode:         { type: Object, required: true },
})

// ─── FILTRES ───
const debut = ref(props.filters.debut)
const fin = ref(props.filters.fin)

const appliquerFiltre = () => {
    router.get('/rapport-rse', { debut: debut.value, fin: fin.value }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Préréglages période
const periodePreset = (preset) => {
    const aujourdhui = new Date()
    let dateDebut, dateFin

    switch (preset) {
        case 'trimestre':
            const mois = aujourdhui.getMonth()
            const trimestre = Math.floor(mois / 3)
            dateDebut = new Date(aujourdhui.getFullYear(), trimestre * 3, 1)
            dateFin = new Date(aujourdhui.getFullYear(), trimestre * 3 + 3, 0)
            break
        case 'annee':
            dateDebut = new Date(aujourdhui.getFullYear(), 0, 1)
            dateFin = new Date(aujourdhui.getFullYear(), 11, 31)
            break
        case 'annee_precedente':
            dateDebut = new Date(aujourdhui.getFullYear() - 1, 0, 1)
            dateFin = new Date(aujourdhui.getFullYear() - 1, 11, 31)
            break
    }

    debut.value = dateDebut.toISOString().split('T')[0]
    fin.value = dateFin.toISOString().split('T')[0]
    appliquerFiltre()
}

// ─── EXPORT PDF (impression navigateur) ───
const exporterPdf = () => {
    window.print()
}

// ─── HELPERS ───
const formaterNombre = (n) => Number(n).toLocaleString('fr-FR')
const formaterMontant = (m) => Number(m).toLocaleString('fr-FR') + ' FCFA'
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

// ─── COULEURS TYPES ───
const couleursTypes = {
    BARA_MOUSSO: '#F43F5E',
    CONF:        '#3B82F6',
    SPORT:       '#06B6D4',
    CHALLENGE:   '#8B5CF6',
    FORMATION:   '#10B981',
    HACK:        '#F97316',
    SALON:       '#6366F1',
}

// ─── DONNÉES GRAPHIQUE TYPES ───
const dataTypes = computed(() => ({
    labels: props.repartitionType.map(t => t.type_nom),
    datasets: [{
        data: props.repartitionType.map(t => t.nombre),
        backgroundColor: props.repartitionType.map(t => couleursTypes[t.type_code] || '#94A3B8'),
        borderWidth: 0,
    }],
}))

// ─── DONNÉES GRAPHIQUE BUDGET PAR TYPE ───
const budgetParType = computed(() => {
    const groupes = {}
    props.evenements.forEach(e => {
        const code = e.type_evenement?.code ?? 'AUTRE'
        const nom = e.type_evenement?.nom ?? 'Autre'
        if (!groupes[code]) {
            groupes[code] = { nom, total: 0 }
        }
        groupes[code].total += Number(e.budget_prev ?? 0)
    })
    return Object.entries(groupes).map(([code, data]) => ({ code, ...data }))
})

const dataBudget = computed(() => ({
    labels: budgetParType.value.map(b => b.nom),
    datasets: [{
        label: 'Budget (FCFA)',
        data: budgetParType.value.map(b => b.total),
        backgroundColor: budgetParType.value.map(b => couleursTypes[b.code] || '#94A3B8'),
        borderRadius: 6,
    }],
}))

// ─── COULEUR STATUT ───
const couleurStatut = (statut) => ({
    publie:        { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Publié' },
    en_cours:      { bg: 'bg-blue-100',    text: 'text-blue-700',    label: 'En cours' },
    termine:       { bg: 'bg-slate-100',   text: 'text-slate-700',   label: 'Terminé' },
    brouillon:     { bg: 'bg-amber-100',   text: 'text-amber-700',   label: 'Brouillon' },
    en_validation: { bg: 'bg-amber-100',   text: 'text-amber-700',   label: 'En validation' },
    annule:        { bg: 'bg-red-100',     text: 'text-red-700',     label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', label: statut })
</script>

<template>
    <DashboardLayout>

        <!-- ═══════════ HEADER ═══════════ -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Module Analyse
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    Rapport RSE
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Synthèse de l'impact social et environnemental
                </p>
            </div>

            <div class="flex gap-2">
                <Link href="/analyses"
                      class="rounded-lg border-2 border-border-soft bg-white px-4 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                    ← Tableau global
                </Link>
                <button @click="exporterPdf"
                        class="rounded-lg bg-moov-noir px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                     Imprimer / Exporter PDF
                </button>
            </div>
        </div>

        <!-- ═══════════ FILTRE PÉRIODE ═══════════ -->
        <div class="mb-6 rounded-xl bg-white p-5 shadow-card no-print">
            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-text-sub">
                Période d'analyse
            </p>

            <!-- Préréglages -->
            <div class="mb-4 flex flex-wrap gap-2">
                <button @click="periodePreset('trimestre')"
                        class="rounded-lg border-2 border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-sub transition hover:bg-moov-blue hover:text-white hover:border-moov-blue">
                    Trimestre en cours
                </button>
                <button @click="periodePreset('annee')"
                        class="rounded-lg border-2 border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-sub transition hover:bg-moov-blue hover:text-white hover:border-moov-blue">
                    Année en cours
                </button>
                <button @click="periodePreset('annee_precedente')"
                        class="rounded-lg border-2 border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-sub transition hover:bg-moov-blue hover:text-white hover:border-moov-blue">
                    Année précédente
                </button>
            </div>

            <!-- Dates personnalisées -->
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
                <div class="flex items-end">
                    <button @click="appliquerFiltre"
                            class="w-full rounded-lg bg-moov-blue px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-700">
                        Appliquer
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ DOCUMENT PRINTABLE ═══════════ -->
        <div class="printable-area space-y-6">

            <!-- ═══════════ EN-TÊTE RAPPORT (visible en print) ═══════════ -->
            <div class="rounded-2xl bg-gradient-to-br from-moov-blue to-blue-700 p-8 text-white shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-white/80">
                            Moov Africa Burkina · dCIRP
                        </p>
                        <h2 class="mt-3 font-display text-3xl font-extrabold leading-tight sm:text-4xl">
                            Rapport RSE
                        </h2>
                        <p class="mt-2 text-base text-white/90">
                            Période du <strong>{{ periode.debut_label }}</strong> au <strong>{{ periode.fin_label }}</strong>
                        </p>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-white/80">
                            Édité le
                        </p>
                        <p class="mt-1 font-display text-xl font-extrabold">
                            {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ═══════════ SYNTHÈSE EXÉCUTIVE ═══════════ -->
            <div class="rounded-2xl bg-white p-6 shadow-card">
                <h3 class="mb-4 font-display text-lg font-extrabold text-text-main">
                     Synthèse exécutive
                </h3>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-border-soft p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Événements</p>
                        <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">
                            {{ kpis.total_evenements }}
                        </p>
                        <p class="mt-1 text-xs text-text-sub">organisés sur la période</p>
                    </div>

                    <div class="rounded-xl border border-border-soft p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Participants</p>
                        <p class="mt-2 font-display text-3xl font-extrabold text-moov-orange">
                            {{ formaterNombre(kpis.total_inscriptions) }}
                        </p>
                        <p class="mt-1 text-xs text-text-sub">inscriptions reçues</p>
                    </div>

                    <div class="rounded-xl border border-border-soft p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bénéficiaires</p>
                        <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">
                            {{ formaterNombre(kpis.cible_beneficiaires) }}
                        </p>
                        <p class="mt-1 text-xs text-text-sub">cible RSE atteinte</p>
                    </div>

                    <div class="rounded-xl border border-border-soft p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Budget</p>
                        <p class="mt-2 font-display text-2xl font-extrabold text-violet-600">
                            {{ formaterNombre(kpis.budget_total) }}
                        </p>
                        <p class="mt-1 text-xs text-text-sub">FCFA engagés</p>
                    </div>
                </div>

                <!-- Indicateurs clés -->
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-xl bg-emerald-50 p-4">
                        <p class="text-xs font-bold text-emerald-700">Taux d'acceptation</p>
                        <p class="mt-1 font-display text-2xl font-extrabold text-emerald-900">
                            {{ kpis.taux_acceptation }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4">
                        <p class="text-xs font-bold text-blue-700">Taux de présence</p>
                        <p class="mt-1 font-display text-2xl font-extrabold text-blue-900">
                            {{ kpis.taux_presence }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-cyan-50 p-4">
                        <p class="text-xs font-bold text-cyan-700">Bénévoles mobilisés</p>
                        <p class="mt-1 font-display text-2xl font-extrabold text-cyan-900">
                            {{ kpis.benevoles_mobilises }} sur {{ kpis.total_postes }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ═══════════ RÉPARTITION & BUDGET ═══════════ -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <div class="rounded-2xl bg-white p-6 shadow-card">
                    <h3 class="mb-4 font-display text-base font-extrabold text-text-main">
                        Répartition par type
                    </h3>
                    <div v-if="repartitionType.length > 0" class="h-64">
                        <DoughnutChart :chart-data="dataTypes"/>
                    </div>
                    <p v-else class="py-12 text-center text-sm text-text-muted">Aucune donnée sur la période</p>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-card">
                    <h3 class="mb-4 font-display text-base font-extrabold text-text-main">
                        Budget par type d'événement
                    </h3>
                    <div v-if="budgetParType.length > 0" class="h-64">
                        <BarChart :chart-data="dataBudget"/>
                    </div>
                    <p v-else class="py-12 text-center text-sm text-text-muted">Aucune donnée sur la période</p>
                </div>
            </div>

            <!-- ═══════════ LISTE DES ÉVÉNEMENTS ═══════════ -->
            <div class="rounded-2xl bg-white p-6 shadow-card">
                <h3 class="mb-4 font-display text-base font-extrabold text-text-main">
                    Détail des événements ({{ evenements.length }})
                </h3>

                <div v-if="evenements.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b-2 border-border-soft">
                            <tr>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-text-sub">Événement</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-text-sub">Type</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-text-sub">Date</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-text-sub">Inscriptions</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-text-sub">Acceptées</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-text-sub">Budget</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-text-sub">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft">
                            <tr v-for="e in evenements" :key="e.id" class="hover:bg-page-bg/50">
                                <td class="px-3 py-3">
                                    <Link :href="`/evenements/${e.id}/dashboard`"
                                          class="font-bold text-text-main hover:text-moov-blue">
                                        {{ e.titre }}
                                    </Link>
                                    <p v-if="e.lieu" class="text-xs text-text-sub">📍 {{ e.lieu.nom }}</p>
                                </td>
                                <td class="px-3 py-3 text-sm text-text-sub">{{ e.type_evenement?.nom }}</td>
                                <td class="px-3 py-3 text-sm text-text-sub">{{ formaterDate(e.date_debut) }}</td>
                                <td class="px-3 py-3 text-right text-sm font-bold text-text-main">
                                    {{ e.inscriptions_count }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-bold text-emerald-600">
                                    {{ e.inscriptions_acceptees_count }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-text-sub">
                                    {{ e.budget_prev ? formaterNombre(e.budget_prev) : '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                        couleurStatut(e.statut).bg, couleurStatut(e.statut).text]">
                                        {{ couleurStatut(e.statut).label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="py-12 text-center text-sm text-text-muted">
                    Aucun événement sur cette période
                </p>
            </div>

            <!-- ═══════════ FOOTER RAPPORT ═══════════ -->
            <div class="rounded-2xl bg-moov-noir p-6 text-center text-white">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/60">
                    Document confidentiel
                </p>
                <p class="mt-2 text-sm text-white/80">
                    © {{ new Date().getFullYear() }} Moov Africa Burkina · Direction de la Communication et des Relations Publiques (dCIRP)
                </p>
                <p class="mt-1 text-xs text-white/60">
                    Ce rapport présente une synthèse de l'engagement RSE de Moov Africa Burkina sur la période sélectionnée.
                </p>
            </div>
        </div>

    </DashboardLayout>
</template>

<style>
/* Styles d'impression PDF */
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .printable-area {
        max-width: 100% !important;
    }
    aside, nav, header.dashboard-header {
        display: none !important;
    }
}
</style>