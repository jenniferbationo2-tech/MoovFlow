<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'

const props = defineProps({
    evenement:           { type: Object, required: true },
    kpis:                { type: Object, required: true },
    statutsInscriptions: { type: Array, required: true },
    objectifRse:         { type: Object, default: null },
    peutModifierRse:     { type: Boolean, default: false },
})

// ─── BILAN D'IMPACT RSE ───
const typesImpact = [
    'Éducation',
    'Santé',
    'Environnement',
    'Inclusion sociale',
    'Emploi & Entrepreneuriat',
    'Sport & Culture',
    'Autre',
]

const rseForm = useForm({
    type_impact:                props.objectifRse?.type_impact ?? typesImpact[0],
    nb_beneficiaires_directs:   props.objectifRse?.nb_beneficiaires_directs ?? props.kpis.beneficiaires_reels,
    nb_beneficiaires_indirects: props.objectifRse?.nb_beneficiaires_indirects ?? 0,
    nb_femmes_beneficiaires:    props.objectifRse?.nb_femmes_beneficiaires ?? 0,
    nb_associations_soutenues:  props.objectifRse?.nb_associations_soutenues ?? 0,
    nb_projets_accompagnes:     props.objectifRse?.nb_projets_accompagnes ?? 0,
    nb_emplois_crees:           props.objectifRse?.nb_emplois_crees ?? 0,
    montants_collectes:         props.objectifRse?.montants_collectes ?? 0,
    retombees_partenaires:      props.objectifRse?.retombees_partenaires ?? 0,
    score_environnemental:      props.objectifRse?.score_environnemental ?? null,
})

const enregistrerBilanRse = () => {
    rseForm.post(`/evenements/${props.evenement.id}/objectifs-rse`, { preserveScroll: true })
}

// ─── HELPERS ───
const formaterNombre = (n) => Number(n).toLocaleString('fr-FR')
const formaterMontant = (m) => Number(m).toLocaleString('fr-FR') + ' FCFA'
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

// ─── COULEURS STATUTS ───
const couleursStatuts = {
    'Pré-inscrits':    '#F59E0B',
    'Présélectionnés': '#3B82F6',
    'Dossier soumis':  '#6366F1',
    'Acceptées':       '#10B981',
    'Présents':        '#059669',
    'Refusées':        '#EF4444',
    'Annulées':        '#94A3B8',
}

// ─── DONNÉES GRAPHIQUE STATUTS (camembert) ───
const dataStatuts = computed(() => {
    const filtered = props.statutsInscriptions.filter(s => s.nombre > 0)
    return {
        labels: filtered.map(s => s.statut),
        datasets: [{
            data: filtered.map(s => s.nombre),
            backgroundColor: filtered.map(s => couleursStatuts[s.statut] || '#94A3B8'),
            borderWidth: 0,
        }],
    }
})

// ─── DONNÉES GRAPHIQUE FUNNEL (barres) ───
const dataFunnel = computed(() => ({
    labels: ['Inscriptions', 'Acceptées', 'Présents'],
    datasets: [{
        label: 'Nombre',
        data: [
            props.kpis.total_inscriptions,
            props.kpis.total_acceptes,
            props.kpis.total_present,
        ],
        backgroundColor: ['#1B4A8B', '#10B981', '#059669'],
        borderRadius: 8,
    }],
}))

// ─── COULEURS TYPE ───
const couleurType = computed(() => {
    const code = props.evenement.type_evenement?.code
    return ({
        BARA_MOUSSO: 'rose',
        CONF:        'blue',
        SPORT:       'cyan',
        CHALLENGE:   'violet',
        FORMATION:   'emerald',
        HACK:        'orange',
        SALON:       'indigo',
    }[code] || 'slate')
})

// ─── A-t-il des inscriptions ? ───
const aDesInscriptions = computed(() => props.kpis.total_inscriptions > 0)
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
                Tableau de bord
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                {{ evenement.titre }}
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-text-sub">
                <span class="rounded-md bg-page-bg px-2.5 py-1 text-xs font-bold uppercase tracking-wider">
                    {{ evenement.type_evenement?.nom }}
                </span>
                <span> {{ formaterDate(evenement.date_debut) }}</span>
                <span v-if="evenement.lieu"> {{ evenement.lieu.nom }}</span>
            </div>
        </div>

        
        <div class="mb-6">
            <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                Engagement
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Total inscriptions
                        </p>
                        <div class="rounded-lg bg-blue-50 p-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-extrabold text-text-main">
                        {{ formaterNombre(kpis.total_inscriptions) }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">
                        Dont {{ kpis.total_refuses }} refusées · {{ kpis.total_annules }} annulées
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Taux d'acceptation
                        </p>
                        <div class="rounded-lg bg-emerald-50 p-2">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-extrabold text-emerald-600">
                        {{ kpis.taux_acceptation }}%
                    </p>
                    <p class="mt-1 text-xs text-text-sub">
                        {{ formaterNombre(kpis.total_acceptes) }} candidats acceptés
                    </p>
                </div>

                <div class="rounded-xl bg-gradient-to-br from-moov-blue to-blue-700 p-5 text-white shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-white/80">
                            Taux de présence
                        </p>
                        <div class="rounded-lg bg-white/20 p-2">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-extrabold">
                        {{ kpis.taux_presence }}%
                    </p>
                    <p class="mt-1 text-xs text-white/80">
                        {{ formaterNombre(kpis.total_present) }} présents le jour J
                    </p>
                </div>
            </div>
        </div>

        <!-- ════════ BUDGET ════════ -->
        <div class="mb-6">
            <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                Budget & Finances
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Budget prévisionnel
                    </p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-text-main">
                        {{ formaterNombre(kpis.budget_previsionnel) }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">FCFA</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Dépenses prestataires
                    </p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-text-main">
                        {{ formaterNombre(kpis.depenses_prestataires) }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">FCFA engagés</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Taux d'engagement
                    </p>
                    <p class="mt-3 font-display text-2xl font-extrabold"
                       :class="kpis.budget_engage_pct > 100 ? 'text-red-600' :
                               kpis.budget_engage_pct > 80 ? 'text-amber-600' : 'text-emerald-600'">
                        {{ kpis.budget_engage_pct }}%
                    </p>
                    <!-- Barre de progression -->
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-page-bg">
                        <div class="h-full rounded-full transition-all"
                             :class="kpis.budget_engage_pct > 100 ? 'bg-red-500' :
                                     kpis.budget_engage_pct > 80 ? 'bg-amber-500' : 'bg-emerald-500'"
                             :style="{ width: Math.min(kpis.budget_engage_pct, 100) + '%' }"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════ RSE ════════ -->
        <div class="mb-6">
            <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                Impact RSE
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Cible bénéficiaires
                        </p>
                        <div class="rounded-lg bg-rose-50 p-2">
                            <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-extrabold text-rose-600">
                        {{ formaterNombre(kpis.cible_beneficiaires) }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Personnes visées</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Bénéficiaires réels
                        </p>
                        <div class="rounded-lg bg-emerald-50 p-2">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-extrabold text-emerald-600">
                        {{ formaterNombre(kpis.beneficiaires_reels) }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Personnes touchées</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Public cible
                    </p>
                    <p class="mt-3 text-base font-bold text-text-main">
                        {{ kpis.public_cible !== '—' ? kpis.public_cible : 'Non défini' }}
                    </p>
                    <p v-if="kpis.cible_beneficiaires > 0" class="mt-1 text-xs text-text-sub">
                        Atteinte :
                        <strong :class="kpis.beneficiaires_reels >= kpis.cible_beneficiaires ? 'text-emerald-600' : 'text-amber-600'">
                            {{ Math.round((kpis.beneficiaires_reels / kpis.cible_beneficiaires) * 100) }}%
                        </strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- ════════ BILAN D'IMPACT RSE ════════ -->
        <div class="mb-6">
            <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                Bilan d'impact RSE
            </h2>
            <div class="rounded-xl bg-white p-5 shadow-card sm:p-6">

                <template v-if="peutModifierRse">
                    <p class="mb-5 text-sm text-text-sub">
                        Déclarez l'impact réel de cet événement. Le nombre de bénéficiaires directs est pré-rempli
                        avec les présences confirmées ; ajustez-le si l'impact dépasse les seuls inscrits.
                    </p>

                    <form @submit.prevent="enregistrerBilanRse" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Type d'impact
                            </label>
                            <select v-model="rseForm.type_impact"
                                    class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option v-for="t in typesImpact" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Bénéficiaires directs *
                            </label>
                            <input v-model.number="rseForm.nb_beneficiaires_directs" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"
                                   :class="rseForm.errors.nb_beneficiaires_directs ? 'border-red-400' : ''"/>
                            <p v-if="rseForm.errors.nb_beneficiaires_directs" class="mt-1 text-xs text-red-600">
                                {{ rseForm.errors.nb_beneficiaires_directs }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Bénéficiaires indirects
                            </label>
                            <input v-model.number="rseForm.nb_beneficiaires_indirects" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Femmes bénéficiaires
                            </label>
                            <input v-model.number="rseForm.nb_femmes_beneficiaires" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"
                                   :class="rseForm.errors.nb_femmes_beneficiaires ? 'border-red-400' : ''"/>
                            <p v-if="rseForm.errors.nb_femmes_beneficiaires" class="mt-1 text-xs text-red-600">
                                {{ rseForm.errors.nb_femmes_beneficiaires }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Associations soutenues
                            </label>
                            <input v-model.number="rseForm.nb_associations_soutenues" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Projets accompagnés
                            </label>
                            <input v-model.number="rseForm.nb_projets_accompagnes" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Emplois créés
                            </label>
                            <input v-model.number="rseForm.nb_emplois_crees" type="number" min="0"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Montants collectés (FCFA)
                            </label>
                            <input v-model.number="rseForm.montants_collectes" type="number" min="0" step="0.01"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Retombées partenaires (FCFA)
                            </label>
                            <input v-model.number="rseForm.retombees_partenaires" type="number" min="0" step="0.01"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-muted">
                                Score environnemental (/100)
                            </label>
                            <input v-model.number="rseForm.score_environnemental" type="number" min="0" max="100" step="0.1"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>

                        <div class="flex items-end sm:col-span-2 lg:col-span-3">
                            <button type="submit" :disabled="rseForm.processing"
                                    class="rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:cursor-not-allowed disabled:opacity-50">
                                {{ objectifRse ? 'Mettre à jour le bilan' : 'Enregistrer le bilan' }}
                            </button>
                        </div>
                    </form>
                </template>

                <template v-else-if="objectifRse">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Type d'impact</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ objectifRse.type_impact }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bénéficiaires directs</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ formaterNombre(objectifRse.nb_beneficiaires_directs) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Femmes bénéficiaires</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ formaterNombre(objectifRse.nb_femmes_beneficiaires) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Associations soutenues</p>
                            <p class="mt-1 text-sm font-bold text-text-main">{{ formaterNombre(objectifRse.nb_associations_soutenues) }}</p>
                        </div>
                    </div>
                </template>

                <div v-else class="py-6 text-center text-sm text-text-sub">
                    Aucun bilan d'impact RSE renseigné pour cet événement.
                </div>
            </div>
        </div>

        <!-- ════════ LOGISTIQUE ════════ -->
        <div class="mb-6">
            <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                Logistique mobilisée
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Matériels</p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-text-main">{{ kpis.nb_materiels }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Prestataires</p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-text-main">{{ kpis.nb_prestataires }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Postes bénévoles</p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-text-main">{{ kpis.nb_postes_benevoles }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bénévoles acceptés</p>
                    <p class="mt-3 font-display text-2xl font-extrabold text-emerald-600">{{ kpis.nb_benevoles_acceptes }}</p>
                </div>
            </div>
        </div>

        <!-- ════════ GRAPHIQUES ════════ -->
        <div v-if="aDesInscriptions" class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Funnel de conversion -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Funnel de conversion
                    </h3>
                    <p class="text-xs text-text-sub">Inscriptions → Acceptées → Présents</p>
                </div>
                <div class="h-72">
                    <BarChart :chart-data="dataFunnel"/>
                </div>
            </div>

            <!-- Répartition par statut -->
            <div class="rounded-xl bg-white p-5 shadow-card">
                <div class="mb-4">
                    <h3 class="font-display text-base font-extrabold text-text-main">
                        Répartition par statut
                    </h3>
                    <p class="text-xs text-text-sub">Détail des inscriptions</p>
                </div>
                <div class="h-72">
                    <DoughnutChart :chart-data="dataStatuts"/>
                </div>
            </div>
        </div>

        <div v-else class="rounded-xl border-2 border-dashed border-border-soft bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="mt-4 font-bold text-text-main">Pas encore de données</p>
            <p class="mt-1 text-sm text-text-sub">
                Les graphiques apparaîtront dès que des inscriptions seront reçues
            </p>
        </div>

    </DashboardLayout>
</template>