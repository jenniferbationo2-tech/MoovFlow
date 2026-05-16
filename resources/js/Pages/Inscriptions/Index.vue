<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    inscriptions: { type: Object, required: true },
    kpis:         { type: Object, required: true },
    evenements:   { type: Array, required: true },
    filters:      { type: Object, default: () => ({}) },
    userRole:     { type: Object, required: true },
})

// Filtres locaux
const filtres = ref({
    statut:       props.filters.statut || '',
    evenement_id: props.filters.evenement_id || '',
    niveau:       props.filters.niveau || '',
    search:       props.filters.search || '',
})

const appliquerFiltres = () => {
    router.get('/inscriptions', filtres.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const resetFiltres = () => {
    filtres.value = { statut: '', evenement_id: '', niveau: '', search: '' }
    appliquerFiltres()
}

let timeoutId = null
watch(() => filtres.value.search, () => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(appliquerFiltres, 400)
})

// Helpers
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const couleurStatut = (statut) => ({
    preinscrit:     { bg: 'bg-amber-100',    text: 'text-amber-700',   dot: 'bg-amber-500' },
    preselectionne: { bg: 'bg-blue-100',     text: 'text-blue-700',    dot: 'bg-blue-500' },
    dossier_soumis: { bg: 'bg-indigo-100',   text: 'text-indigo-700',  dot: 'bg-indigo-500' },
    en_analyse:     { bg: 'bg-violet-100',   text: 'text-violet-700',  dot: 'bg-violet-500' },
    recommandee:    { bg: 'bg-cyan-100',     text: 'text-cyan-700',    dot: 'bg-cyan-500' },
    acceptee:       { bg: 'bg-emerald-100',  text: 'text-emerald-700', dot: 'bg-emerald-500' },
    confirmee:      { bg: 'bg-emerald-200',  text: 'text-emerald-900', dot: 'bg-emerald-600' },
    refusee:        { bg: 'bg-red-100',      text: 'text-red-700',     dot: 'bg-red-500' },
    present:        { bg: 'bg-emerald-200',  text: 'text-emerald-900', dot: 'bg-emerald-600' },
    annulee:        { bg: 'bg-slate-100',    text: 'text-slate-600',   dot: 'bg-slate-400' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400' })

const labelStatut = (statut) => ({
    preinscrit: 'Pré-inscrit', preselectionne: 'Présélectionné',
    dossier_soumis: 'Dossier soumis', en_analyse: 'En analyse',
    recommandee: 'Recommandée', acceptee: 'Acceptée',
    confirmee: 'Confirmée', refusee: 'Refusée',
    present: 'Présent', annulee: 'Annulée',
}[statut] || statut)
</script>

<template>
    <DashboardLayout>

        <!-- En-tête -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Gestion staff
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main">
                Inscriptions
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                {{ userRole.estResponsable ? 'Toutes les inscriptions de la plateforme' : 'Inscriptions à vos événements' }}
            </p>
        </div>

        <!-- KPIs cliquables -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">

            <button @click="filtres.statut = ''; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        !filtres.statut ? 'bg-moov-noir text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    !filtres.statut ? 'text-white/60' : 'text-text-muted']">
                    Total
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    !filtres.statut ? 'text-moov-orange' : 'text-text-main']">
                    {{ kpis.total }}
                </p>
            </button>

            <button @click="filtres.statut = 'preinscrit'; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtres.statut === 'preinscrit' ? 'bg-amber-500 text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    filtres.statut === 'preinscrit' ? 'text-white/70' : 'text-text-muted']">
                    Pré-inscrits
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    filtres.statut === 'preinscrit' ? 'text-white' : 'text-amber-600']">
                    {{ kpis.preinscrits }}
                </p>
            </button>

            <button @click="filtres.statut = 'dossier_soumis'; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtres.statut === 'dossier_soumis' ? 'bg-indigo-500 text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    filtres.statut === 'dossier_soumis' ? 'text-white/70' : 'text-text-muted']">
                    À analyser
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    filtres.statut === 'dossier_soumis' ? 'text-white' : 'text-indigo-600']">
                    {{ kpis.a_analyser }}
                </p>
            </button>

            <button @click="filtres.statut = 'recommandee'; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtres.statut === 'recommandee' ? 'bg-cyan-500 text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    filtres.statut === 'recommandee' ? 'text-white/70' : 'text-text-muted']">
                    Recommandées
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    filtres.statut === 'recommandee' ? 'text-white' : 'text-cyan-600']">
                    {{ kpis.recommandees }}
                </p>
            </button>

            <button @click="filtres.statut = 'confirmee'; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtres.statut === 'confirmee' ? 'bg-emerald-500 text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    filtres.statut === 'confirmee' ? 'text-white/70' : 'text-text-muted']">
                    Acceptées
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    filtres.statut === 'confirmee' ? 'text-white' : 'text-emerald-600']">
                    {{ kpis.acceptees }}
                </p>
            </button>

            <button @click="filtres.statut = 'refusee'; appliquerFiltres()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtres.statut === 'refusee' ? 'bg-red-500 text-white' : 'bg-white']">
                <p :class="['text-xs font-bold uppercase tracking-wider',
                    filtres.statut === 'refusee' ? 'text-white/70' : 'text-text-muted']">
                    Refusées
                </p>
                <p :class="['mt-2 font-display text-2xl font-extrabold',
                    filtres.statut === 'refusee' ? 'text-white' : 'text-red-600']">
                    {{ kpis.refusees }}
                </p>
            </button>
        </div>

        <!-- Filtres avancés -->
        <div class="mb-6 rounded-xl bg-white p-5 shadow-card">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">

                <!-- Recherche -->
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Rechercher
                    </label>
                    <input v-model="filtres.search" type="text"
                           placeholder="Nom, prénom ou email..."
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none transition focus:border-moov-blue"/>
                </div>

                <!-- Événement -->
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Événement
                    </label>
                    <select v-model="filtres.evenement_id" @change="appliquerFiltres"
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none transition focus:border-moov-blue">
                        <option value="">Tous les événements</option>
                        <option v-for="e in evenements" :key="e.id" :value="e.id">{{ e.titre }}</option>
                    </select>
                </div>

                <!-- Niveau -->
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Niveau
                    </label>
                    <select v-model="filtres.niveau" @change="appliquerFiltres"
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none transition focus:border-moov-blue">
                        <option value="">Tous</option>
                        <option value="niveau_1">Niveau 1 (pré-inscriptions)</option>
                        <option value="niveau_2">Niveau 2 (dossiers complets)</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-3 flex items-center justify-between gap-2 border-t border-border-soft pt-3">
                <p class="text-xs text-text-muted">
                    {{ inscriptions.total }} inscription(s) trouvée(s)
                </p>
                <button @click="resetFiltres"
                        class="text-xs font-bold text-text-sub transition hover:text-moov-blue">
                    Réinitialiser les filtres
                </button>
            </div>
        </div>

        <!-- Liste -->
        <div v-if="inscriptions.data.length > 0" class="space-y-2">
            <Link v-for="insc in inscriptions.data" :key="insc.id"
                  :href="`/inscriptions/${insc.id}`"
                  class="block rounded-xl bg-white p-5 shadow-card transition hover:shadow-card-hover">

                <div class="flex flex-wrap items-start justify-between gap-3">

                    <!-- Infos candidat -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-display text-base font-extrabold text-text-main">
                                {{ insc.user?.prenom }} {{ insc.user?.nom }}
                            </h3>
                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                couleurStatut(insc.statut).bg, couleurStatut(insc.statut).text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(insc.statut).dot]"/>
                                {{ labelStatut(insc.statut) }}
                            </span>
                            <span v-if="insc.niveau_inscription === 'niveau_2'"
                                  class="rounded bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-violet-700">
                                Dossier complet
                            </span>
                        </div>

                        <p class="mt-1 text-xs text-text-sub">
                            {{ insc.user?.email }} <span v-if="insc.user?.telephone">· {{ insc.user.telephone }}</span>
                        </p>

                        <p class="mt-2 text-sm font-bold text-text-main">
                            {{ insc.evenement?.titre }}
                        </p>
                        <p class="text-xs text-text-sub">
                            {{ insc.evenement?.type_evenement?.nom }} · {{ formaterDate(insc.evenement?.date_debut) }}
                        </p>
                    </div>

                    <!-- Actions visuelles -->
                    <div class="flex flex-col items-end gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                            Soumis le
                        </p>
                        <p class="text-xs font-medium text-text-main">
                            {{ formaterDate(insc.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Motif refus si refusée -->
                <p v-if="insc.statut === 'refusee' && insc.motif_refus"
                   class="mt-3 rounded-lg bg-red-50 border-l-4 border-red-500 px-3 py-2 text-xs text-red-800">
                    <strong>Motif :</strong> {{ insc.motif_refus }}
                </p>
            </Link>

            <!-- Pagination -->
            <div v-if="inscriptions.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                <Link v-for="link in inscriptions.links" :key="link.label"
                      :href="link.url || ''"
                      v-html="link.label"
                      :class="['rounded-lg px-3 py-1.5 text-xs font-bold transition',
                          link.active ? 'bg-moov-blue text-white' :
                          link.url ? 'bg-white text-text-sub hover:bg-page-bg' :
                          'cursor-not-allowed bg-page-bg/50 text-text-muted']"/>
            </div>
        </div>

        <!-- État vide -->
        <div v-else class="rounded-xl border-2 border-dashed border-border-soft bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-4 font-bold text-text-main">Aucune inscription trouvée</p>
            <p class="mt-1 text-sm text-text-sub">
                Essayez de modifier les filtres
            </p>
        </div>
    </DashboardLayout>
</template>