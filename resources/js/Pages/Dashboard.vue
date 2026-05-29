<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    Title,
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend, Title)

const props = defineProps({
    role:                   String,
    kpis:                   Object,
    // Admin
    activiteRecente:        Array,
    prochainsEvenements:    Array,
    repartitionRoles:       Array,
    completionEvenements:   Object,
    // Responsable
    statsRSE:               Object,
    performanceTypes:       Array,
    derniersEvenements:     Array,
    // Organisateur
    mesEvenements:          Array,
    dossiersRecents:        Array,
    // Participant
    mesProchainsEvenements: Array,
    recommandes:            Array,
    // NOUVEAU — Notifications + Camembert
    notifications:          { type: Array, default: () => [] },
    repartitionTypes:       { type: Array, default: () => [] },
})

const page = usePage()
const userPrenom = computed(() => page.props.auth?.user?.prenom ?? '')

const dateAujourdhui = computed(() => {
    return new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    })
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const formaterDateRelative = (d) => {
    if (!d) return '—'
    const date = new Date(d)
    const diff = Math.floor((new Date() - date) / 1000)
    if (diff < 60) return 'À l\'instant'
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`
    return formaterDate(d)
}

const labelStatutInscription = (s) => ({
    en_attente: 'a soumis un dossier',
    en_analyse: 'dossier en analyse',
    acceptee:   'dossier accepté',
    confirmee:  'inscription confirmée',
    refusee:    'dossier refusé',
    present:    'présent à l\'événement',
}[s] || s)

const couleurStatutInscription = (s) => ({
    en_attente: 'bg-amber-100 text-amber-700',
    en_analyse: 'bg-blue-100 text-blue-700',
    acceptee:   'bg-emerald-100 text-emerald-700',
    confirmee:  'bg-emerald-200 text-emerald-800',
    refusee:    'bg-red-100 text-red-700',
    present:    'bg-emerald-200 text-emerald-900',
}[s] || 'bg-slate-100 text-slate-600')

const labelRole = (r) => ({
    admin:             'Administrateurs',
    responsable_dcirp: 'Responsables dCIRP',
    organisateur:      'Organisateurs',
    participant:       'Participants',
    intervenant:       'Intervenants',
    benevole:          'Bénévoles',
    jury:              'Jury',
}[r] || r)

const couleurRole = (r) => ({
    admin:             'bg-red-500',
    responsable_dcirp: 'bg-purple-500',
    organisateur:      'bg-orange-500',
    participant:       'bg-emerald-500',
}[r] || 'bg-slate-400')

const couleurType = (code) => ({
    BARA_MOUSSO: 'bg-amber-50 text-amber-700',
    CONF:        'bg-rose-50 text-rose-700',
    SPORT:       'bg-blue-50 text-blue-700',
    CHALLENGE:   'bg-violet-50 text-violet-700',
    FORMATION:   'bg-emerald-50 text-emerald-700',
    HACK:        'bg-orange-50 text-orange-700',
    SALON:       'bg-indigo-50 text-indigo-700',
}[code] || 'bg-slate-50 text-slate-700')

const couleurTypeFond = (code) => ({
    BARA_MOUSSO: 'bg-amber-100',
    CONF:        'bg-rose-100',
    SPORT:       'bg-blue-100',
    CHALLENGE:   'bg-violet-100',
    FORMATION:   'bg-emerald-100',
    HACK:        'bg-orange-100',
    SALON:       'bg-indigo-100',
}[code] || 'bg-slate-100')

const couleurTypeText = (code) => ({
    BARA_MOUSSO: 'text-amber-700',
    CONF:        'text-rose-700',
    SPORT:       'text-blue-700',
    CHALLENGE:   'text-violet-700',
    FORMATION:   'text-emerald-700',
    HACK:        'text-orange-700',
    SALON:       'text-indigo-700',
}[code] || 'text-slate-700')

const couleurStatutEvenement = (s) => ({
    brouillon: 'bg-amber-100 text-amber-700',
    publie:    'bg-emerald-100 text-emerald-700',
    en_cours:  'bg-blue-100 text-blue-700',
    termine:   'bg-slate-100 text-slate-600',
    annule:    'bg-red-100 text-red-700',
}[s] || 'bg-slate-100 text-slate-600')

const initiales = (nom) => nom.split(' ').map(s => s[0]).join('').toUpperCase().substring(0, 2)

const totalUtilisateurs = computed(() =>
    (props.repartitionRoles ?? []).reduce((acc, r) => acc + r.total, 0)
)

const totalEvenements = computed(() => {
    if (!props.completionEvenements) return 0
    return Object.values(props.completionEvenements).reduce((acc, v) => acc + v, 0)
})
const maxPerformance = computed(() => {
    if (!props.performanceTypes?.length) return 0
    return Math.max(...props.performanceTypes.map(p => p.total))
})


const couleursTypeHex = {
    BARA_MOUSSO: '#f59e0b',  // amber
    CONF:        '#e11d48',  // rose
    SPORT:       '#3b82f6',  // blue
    CHALLENGE:   '#8b5cf6',  // violet
    FORMATION:   '#10b981',  // emerald
    HACK:        '#f97316',  // orange
    SALON:       '#6366f1',  // indigo
}


const chartData = computed(() => {
    const types = props.repartitionTypes ?? []
    return {
        labels: types.map(t => t.nom),
        datasets: [{
            data: types.map(t => t.total),
            backgroundColor: types.map(t => couleursTypeHex[t.code] ?? '#94a3b8'),
            borderColor: '#fff',
            borderWidth: 3,
            hoverOffset: 8,
        }]
    }
})

// Données pour le Doughnut Chart RESPONSABLE (performance par type)
const chartDataPerformance = computed(() => {
    const types = props.performanceTypes ?? []
    return {
        labels: types.map(t => t.type),
        datasets: [{
            data: types.map(t => t.total),
            backgroundColor: types.map(t => couleursTypeHex[t.code] ?? '#94a3b8'),
            borderColor: '#fff',
            borderWidth: 3,
            hoverOffset: 8,
        }]
    }
})

const totalPerformance = computed(() =>
    (props.performanceTypes ?? []).reduce((acc, t) => acc + t.total, 0)
)

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0F172A',
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 13, weight: 'bold' },
            bodyFont: { size: 12 },
            callbacks: {
                label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0)
                    const pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0
                    return ` ${context.parsed} événement(s) · ${pct}%`
                }
            }
        }
    }
}

const totalEvenementsTypes = computed(() =>
    (props.repartitionTypes ?? []).reduce((acc, t) => acc + t.total, 0)
)

const styleNotification = (type) => ({
    danger:  { border: 'border-red-500',     bg: 'bg-red-50',     text: 'text-red-700',     icon: 'bg-red-100 text-red-600' },
    warning: { border: 'border-amber-500',   bg: 'bg-amber-50',   text: 'text-amber-700',   icon: 'bg-amber-100 text-amber-600' },
    info:    { border: 'border-blue-500',    bg: 'bg-blue-50',    text: 'text-blue-700',    icon: 'bg-blue-100 text-blue-600' },
    success: { border: 'border-emerald-500', bg: 'bg-emerald-50', text: 'text-emerald-700', icon: 'bg-emerald-100 text-emerald-600' },
}[type] || { border: 'border-slate-400', bg: 'bg-slate-50', text: 'text-slate-700', icon: 'bg-slate-100 text-slate-600' })
</script>

<template>
    <DashboardLayout>

        
        <div v-if="role === 'admin'">

            <!-- En-tête -->
            <div class="mb-8">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    {{ dateAujourdhui }}
                </p>
                <h1 class="mt-1 font-display text-3xl font-extrabold text-text-main">
                    Bonjour, {{ userPrenom }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Vue d'ensemble du système et de l'activité
                </p>
            </div>

            <!-- KPIs -->
            <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Link href="/admin/users"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Utilisateurs actifs</p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ kpis.utilisateurs_actifs }}</p>
                    <p class="mt-1 text-xs text-text-sub">Voir tous →</p>
                </Link>

                <Link href="/evenements"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Événements publiés</p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ kpis.evenements_publies }}</p>
                    <p class="mt-1 text-xs text-text-sub">Gérer →</p>
                </Link>

                <Link href="/inscriptions?statut=en_attente"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Dossiers à analyser</p>
                    <p class="mt-2 font-display text-3xl font-extrabold"
                       :class="kpis.dossiers_a_analyser > 0 ? 'text-amber-600' : 'text-text-muted'">
                        {{ kpis.dossiers_a_analyser }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">{{ kpis.dossiers_a_analyser > 0 ? 'Action requise →' : 'À jour' }}</p>
                </Link>

                <Link href="/admin/users?statut=bloque"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Comptes bloqués</p>
                    <p class="mt-2 font-display text-3xl font-extrabold"
                       :class="kpis.comptes_bloques > 0 ? 'text-red-600' : 'text-text-muted'">
                        {{ kpis.comptes_bloques }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">{{ kpis.comptes_bloques > 0 ? 'Vérifier →' : 'Aucun blocage' }}</p>
                </Link>
            </div>

            <!-- Activité + Prochains -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Activité récente</h2>
                        <p class="mt-1 text-xs text-text-sub">5 dernières actions sur la plateforme</p>
                    </div>
                    <div v-if="activiteRecente?.length" class="divide-y divide-border-soft">
                        <div v-for="a in activiteRecente" :key="a.id"
                             class="flex items-start gap-3 p-4 transition hover:bg-page-bg/50">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                {{ initiales(a.user) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-text-main">
                                    <span class="font-bold">{{ a.user }}</span>
                                    {{ labelStatutInscription(a.statut) }}
                                </p>
                                <p class="text-xs text-text-sub">Pour : <span class="font-bold">{{ a.evenement }}</span></p>
                                <p class="mt-1 text-xs text-text-muted">{{ formaterDateRelative(a.created_at) }}</p>
                            </div>
                            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurStatutInscription(a.statut)]">
                                {{ a.statut.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="p-10 text-center text-sm text-text-sub">
                        Aucune activité récente
                    </div>
                </div>

                <div class="rounded-xl bg-card shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h2 class="font-display text-base font-bold text-text-main">Prochains événements</h2>
                        <p class="mt-1 text-xs text-text-sub">Événements publiés à venir</p>
                    </div>
                    <div v-if="prochainsEvenements?.length" class="divide-y divide-border-soft">
                        <Link v-for="ev in prochainsEvenements" :key="ev.id"
                              :href="`/evenements/${ev.id}`"
                              class="flex items-start gap-3 p-4 transition hover:bg-page-bg/50">
                            <div class="flex h-12 w-12 flex-shrink-0 flex-col items-center justify-center rounded-lg bg-page-bg">
                                <p class="font-display text-base font-extrabold leading-none text-text-main">
                                    {{ new Date(ev.date_debut).getDate().toString().padStart(2, '0') }}
                                </p>
                                <p class="text-[10px] font-bold uppercase text-text-sub">
                                    {{ new Date(ev.date_debut).toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase().replace('.', '') }}
                                </p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 font-bold text-text-main">{{ ev.titre }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-text-sub">
                                    <span v-if="ev.lieu">{{ ev.lieu }}</span>
                                    <span v-if="ev.lieu && ev.type">·</span>
                                    <span v-if="ev.type"
                                          :class="['rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                              couleurType(ev.type.code)]">
                                        {{ ev.type.nom }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="p-10 text-center text-sm text-text-sub">
                        Aucun événement à venir
                    </div>
                </div>
            </div>

           <!-- ════ NOTIFICATIONS (panneau d'alertes) ════ -->
            <div v-if="notifications.length > 0" class="mb-8 rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-5">
                    <h2 class="flex items-center gap-2 font-display text-base font-bold text-text-main">
                        <svg class="h-5 w-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Notifications
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">Actions importantes</p>
                </div>
                <div class="divide-y divide-border-soft">
                    <component v-for="(notif, i) in notifications" :key="i"
                          :is="notif.href ? 'a' : 'div'"
                          :href="notif.href"
                          :class="['flex items-center gap-3 p-4 border-l-4 transition',
                              styleNotification(notif.type).border,
                              notif.href ? 'hover:bg-page-bg/50 cursor-pointer' : '']">
                        <div :class="['flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full',
                            styleNotification(notif.type).icon]">
                            <svg v-if="notif.type === 'success'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg v-else-if="notif.type === 'danger'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <svg v-else-if="notif.type === 'warning'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p :class="['flex-1 text-sm font-bold', styleNotification(notif.type).text]">
                            {{ notif.titre }}
                        </p>
                        <span v-if="notif.href" :class="['text-sm font-bold', styleNotification(notif.type).text]">→</span>
                    </component>
                </div>
            </div>

            <!-- ════ CAMEMBERT + STATUT ÉVÉNEMENTS ════ -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- GAUCHE : Camembert répartition événements par type -->
                <div class="rounded-xl bg-card p-6 shadow-card">
                    <h2 class="mb-1 font-display text-base font-bold text-text-main">
                        Répartition des événements
                    </h2>
                    <p class="mb-5 text-xs text-text-sub">
                        Total : {{ totalEvenementsTypes }} événement{{ totalEvenementsTypes > 1 ? 's' : '' }} par typologie
                    </p>

                    <div v-if="totalEvenementsTypes > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-center">
                        <!-- Camembert -->
                        <div class="relative h-56">
                            <Doughnut :data="chartData" :options="chartOptions" />
                            <!-- Total au centre -->
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <p class="font-display text-3xl font-extrabold text-text-main">
                                    {{ totalEvenementsTypes }}
                                </p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                    Au total
                                </p>
                            </div>
                        </div>

                        <!-- Légende compacte -->
                        <div class="space-y-2">
                            <div v-for="t in repartitionTypes" :key="t.code"
                                 class="flex items-center gap-2 text-sm">
                                <span class="h-3 w-3 flex-shrink-0 rounded-full"
                                      :style="{ backgroundColor: couleursTypeHex[t.code] || '#94a3b8' }"/>
                                <span class="flex-1 truncate text-text-sub">{{ t.nom }}</span>
                                <span :class="['font-bold', t.total > 0 ? 'text-text-main' : 'text-text-muted']">
                                    {{ t.total }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-10 text-center text-sm text-text-sub">
                        Aucun événement à analyser
                    </div>
                </div>

            
                <div class="rounded-xl bg-card p-6 shadow-card">
                    <h2 class="mb-1 font-display text-base font-bold text-text-main">Statut des événements</h2>
                    <p class="mb-5 text-xs text-text-sub">Total : {{ totalEvenements }} événements</p>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-lg bg-amber-50/50 px-3 py-2">
                            <span class="font-bold text-amber-700">Brouillon</span>
                            <span class="font-display text-xl font-extrabold text-amber-600">{{ completionEvenements?.brouillon ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-emerald-50/50 px-3 py-2">
                            <span class="font-bold text-emerald-700">Publié</span>
                            <span class="font-display text-xl font-extrabold text-emerald-600">{{ completionEvenements?.publie ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-blue-50/50 px-3 py-2">
                            <span class="font-bold text-blue-700">En cours</span>
                            <span class="font-display text-xl font-extrabold text-blue-600">{{ completionEvenements?.en_cours ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span class="font-bold text-slate-700">Terminé</span>
                            <span class="font-display text-xl font-extrabold text-slate-600">{{ completionEvenements?.termine ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-red-50/50 px-3 py-2">
                            <span class="font-bold text-red-700">Annulé</span>
                            <span class="font-display text-xl font-extrabold text-red-600">{{ completionEvenements?.annule ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else-if="role === 'organisateur'">

            <!-- En-tête -->
            <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        {{ dateAujourdhui }}
                    </p>
                    <h1 class="mt-1 font-display text-3xl font-extrabold text-text-main">
                        Bonjour, {{ userPrenom }}
                    </h1>
                    <p class="mt-1 text-sm text-text-sub">
                        Vue d'ensemble de vos événements et candidatures
                    </p>
                </div>
                <Link href="/evenements/create"
                      class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    + Nouvel Événement
                </Link>
            </div>

            <!-- KPIs Organisateur -->
            <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">

                <Link href="/evenements"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Mes événements actifs
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">
                        {{ kpis.mes_evenements_actifs }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Voir tous →</p>
                </Link>

                <Link href="/inscriptions?statut=en_attente"
                      :class="['rounded-xl p-5 shadow-card transition hover:shadow-card-hover',
                          kpis.dossiers_a_analyser > 0
                              ? 'bg-amber-50 border-2 border-amber-300'
                              : 'bg-card']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Dossiers à analyser
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold"
                       :class="kpis.dossiers_a_analyser > 0 ? 'text-amber-600' : 'text-text-muted'">
                        {{ kpis.dossiers_a_analyser }}
                    </p>
                    <p class="mt-1 text-xs"
                       :class="kpis.dossiers_a_analyser > 0 ? 'text-amber-700 font-bold' : 'text-text-sub'">
                        {{ kpis.dossiers_a_analyser > 0 ? 'Action requise →' : 'Aucun en attente' }}
                    </p>
                </Link>

                <Link href="/inscriptions"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Inscriptions totales
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">
                        {{ kpis.inscriptions_totales }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Tous statuts confondus</p>
                </Link>

                <Link href="/evenements"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Brouillons à publier
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold"
                       :class="kpis.evenements_brouillon > 0 ? 'text-orange-600' : 'text-text-muted'">
                        {{ kpis.evenements_brouillon }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">
                        {{ kpis.evenements_brouillon > 0 ? 'À finaliser' : 'Aucun en attente' }}
                    </p>
                </Link>
            </div>

            <!-- Dossiers en attente (PRIORITÉ) -->
            <div v-if="dossiersRecents?.length" class="mb-8 rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-display text-base font-bold text-text-main">
                                Dossiers en attente d'analyse
                            </h2>
                            <p class="mt-1 text-xs text-text-sub">
                                Candidats à valider pour vos événements
                            </p>
                        </div>
                        <Link href="/inscriptions?statut=en_attente"
                              class="text-xs font-bold text-moov-orange hover:text-moov-orange-dark">
                            Voir tous →
                        </Link>
                    </div>
                </div>
                <div class="divide-y divide-border-soft">
                    <Link v-for="d in dossiersRecents" :key="d.id"
                          :href="`/inscriptions/${d.id}`"
                          class="flex items-start gap-3 p-4 transition hover:bg-page-bg/50">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">
                            {{ initiales((d.user?.prenom ?? '') + ' ' + (d.user?.nom ?? '')) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-text-main">
                                {{ d.user?.prenom }} {{ d.user?.nom }}
                            </p>
                            <p class="text-xs text-text-sub">
                                Pour : <span class="font-bold">{{ d.evenement }}</span>
                            </p>
                            <p class="mt-1 text-xs text-text-muted">
                                Soumis {{ formaterDateRelative(d.created_at) }}
                                · Réf : <span class="font-mono font-bold">{{ d.qr_code }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurStatutInscription(d.statut)]">
                                {{ d.statut.replace('_', ' ') }}
                            </span>
                            <span class="text-xs font-bold text-moov-blue">Examiner →</span>
                        </div>
                    </Link>
                </div>
            </div>
            <div v-else class="mb-8 rounded-xl border-2 border-dashed border-border-soft bg-white p-10 text-center">
                <p class="font-bold text-emerald-600">✓ Aucun dossier en attente</p>
                <p class="mt-1 text-sm text-text-sub">Tous vos dossiers ont été traités</p>
            </div>

            <!-- Mes événements -->
            <div class="rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-display text-base font-bold text-text-main">
                                Mes événements récents
                            </h2>
                            <p class="mt-1 text-xs text-text-sub">5 derniers créés</p>
                        </div>
                        <Link href="/evenements"
                              class="text-xs font-bold text-moov-orange hover:text-moov-orange-dark">
                            Voir tous →
                        </Link>
                    </div>
                </div>

                <div v-if="mesEvenements?.length" class="divide-y divide-border-soft">
                    <Link v-for="ev in mesEvenements" :key="ev.id"
                          :href="`/evenements/${ev.id}`"
                          class="flex items-start gap-4 p-4 transition hover:bg-page-bg/50">

                        <!-- Date overlay -->
                        <div class="flex h-14 w-14 flex-shrink-0 flex-col items-center justify-center rounded-lg bg-page-bg">
                            <p class="font-display text-lg font-extrabold leading-none text-text-main">
                                {{ ev.date_debut ? new Date(ev.date_debut).getDate().toString().padStart(2, '0') : '--' }}
                            </p>
                            <p class="text-[10px] font-bold uppercase text-text-sub">
                                {{ ev.date_debut
                                    ? new Date(ev.date_debut).toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase().replace('.', '')
                                    : '---' }}
                            </p>
                        </div>

                        <!-- Infos -->
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-1 font-bold text-text-main">{{ ev.titre }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-text-sub">
                                <span v-if="ev.lieu">{{ ev.lieu }}</span>
                                <span v-if="ev.lieu && ev.type">·</span>
                                <span v-if="ev.type"
                                      :class="['rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                          couleurType(ev.type.code)]">
                                    {{ ev.type.nom }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 text-right">
                            <div>
                                <p class="font-display text-xl font-extrabold text-moov-blue">
                                    {{ ev.inscriptions_count }}
                                </p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                    Inscrits
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurStatutEvenement(ev.statut)]">
                                {{ ev.statut.replace('_', ' ') }}
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else class="p-12 text-center">
                    <p class="font-bold text-text-main">Aucun événement créé</p>
                    <p class="mt-1 text-sm text-text-sub">Commencez par créer votre premier événement</p>
                    <Link href="/evenements/create"
                          class="mt-4 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                        + Créer un événement
                    </Link>
                </div>
            </div>
        </div>
   
        <div v-else-if="role === 'participant'">

            <!-- En-tête personnalisé -->
            <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-moov-blue to-moov-blue-dark p-8 text-white">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">
                            {{ dateAujourdhui }}
                        </p>
                        <h1 class="mt-2 font-display text-3xl font-extrabold">
                            Bonjour, {{ userPrenom }}
                        </h1>
                        <p class="mt-2 text-sm text-white/80">
                            Bienvenue sur votre espace MoovFlow 
                        </p>
                    </div>
                    <Link href="/evenements"
                          class="rounded-lg bg-moov-orange px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-orange-dark">
                        Découvrir les événements →
                    </Link>
                </div>
            </div>

            <!-- KPIs personnels -->
            <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">

                <Link href="/mes-inscriptions"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-moov-blue-50">
                            <svg class="h-6 w-6 text-moov-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Mes inscriptions
                            </p>
                            <p class="mt-1 font-display text-3xl font-extrabold text-moov-blue">
                                {{ kpis.mes_inscriptions }}
                            </p>
                        </div>
                    </div>
                </Link>

                <Link href="/mes-inscriptions?statut=confirmee"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Événements à venir
                            </p>
                            <p class="mt-1 font-display text-3xl font-extrabold text-emerald-600">
                                {{ kpis.a_venir }}
                            </p>
                        </div>
                    </div>
                </Link>

                <Link href="/mes-inscriptions?statut=en_attente"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50">
                            <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                En attente
                            </p>
                            <p class="mt-1 font-display text-3xl font-extrabold"
                               :class="kpis.en_attente > 0 ? 'text-amber-600' : 'text-text-muted'">
                                {{ kpis.en_attente }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Mes prochains événements -->
            <div v-if="mesProchainsEvenements?.length" class="mb-8">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-xl font-extrabold text-text-main">
                            Mes prochains événements
                        </h2>
                        <p class="mt-1 text-sm text-text-sub">
                            Vos inscriptions confirmées
                        </p>
                    </div>
                    <Link href="/mes-inscriptions"
                          class="text-sm font-bold text-moov-orange hover:text-moov-orange-dark">
                        Voir toutes →
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="i in mesProchainsEvenements" :key="i.id"
                          :href="`/inscriptions/${i.id}`"
                          class="group overflow-hidden rounded-xl bg-card shadow-card transition hover:shadow-card-hover">

                        <!-- Bandeau coloré par type -->
                        <div :class="['flex h-32 items-center justify-center',
                            couleurTypeFond(i.evenement?.type?.code)]">
                            <p :class="['font-display text-5xl font-extrabold opacity-30',
                                couleurTypeText(i.evenement?.type?.code)]">
                                {{ i.evenement?.type?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                            </p>
                        </div>

                        <!-- Infos -->
                        <div class="p-4">
                            <span :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurType(i.evenement?.type?.code)]">
                                {{ i.evenement?.type?.nom ?? '—' }}
                            </span>

                            <h3 class="mt-2 font-display text-base font-extrabold leading-tight text-text-main line-clamp-2">
                                {{ i.evenement?.titre }}
                            </h3>

                            <div class="mt-3 flex items-center gap-2 text-xs text-text-sub">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ formaterDate(i.evenement?.date_debut) }}</span>
                            </div>
                            <div v-if="i.evenement?.lieu" class="mt-1 flex items-center gap-2 text-xs text-text-sub">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="line-clamp-1">{{ i.evenement.lieu }}</span>
                            </div>

                            <!-- Statut + QR code -->
                            <div class="mt-4 flex items-center justify-between border-t border-border-soft pt-3">
                                <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                    i.statut === 'confirmee' ? 'bg-emerald-100 text-emerald-800'
                                    : 'bg-amber-100 text-amber-700']">
                                    <span :class="['h-1.5 w-1.5 rounded-full',
                                        i.statut === 'confirmee' ? 'bg-emerald-600' : 'bg-amber-500']"/>
                                    {{ i.statut === 'confirmee' ? 'Confirmée' : 'Acceptée' }}
                                </span>
                                <p class="font-mono text-[10px] text-text-muted">{{ i.qr_code }}</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Aucun événement à venir -->
            <div v-else class="mb-8 rounded-xl border-2 border-dashed border-border-soft bg-white p-10 text-center">
                <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="mt-4 font-bold text-text-main">Aucun événement à venir</p>
                <p class="mt-1 text-sm text-text-sub">Découvrez les événements ouverts aux inscriptions</p>
                <Link href="/evenements"
                      class="mt-4 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                    Voir les événements →
                </Link>
            </div>

            <!-- Recommandations -->
            <div v-if="recommandes?.length">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-xl font-extrabold text-text-main">
                            Recommandations pour vous
                        </h2>
                        <p class="mt-1 text-sm text-text-sub">
                            Événements ouverts aux inscriptions
                        </p>
                    </div>
                    <Link href="/evenements"
                          class="text-sm font-bold text-moov-orange hover:text-moov-orange-dark">
                        Voir tous →
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Link v-for="ev in recommandes" :key="ev.id"
                          :href="`/evenements/${ev.id}`"
                          class="group overflow-hidden rounded-xl bg-card shadow-card transition hover:shadow-card-hover">

                        <!-- Visuel ou placeholder -->
                        <div class="relative h-32 overflow-hidden">
                            <img v-if="ev.visuel_url"
                                 :src="ev.visuel_url"
                                 :alt="ev.titre"
                                 class="h-full w-full object-cover transition group-hover:scale-105"/>
                            <div v-else
                                 :class="['flex h-full w-full items-center justify-center',
                                     couleurTypeFond(ev.type?.code)]">
                                <p :class="['font-display text-5xl font-extrabold opacity-30',
                                    couleurTypeText(ev.type?.code)]">
                                    {{ ev.type?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                                </p>
                            </div>

                            <!-- Date overlay -->
                            <div v-if="ev.date_debut"
                                 class="absolute left-3 top-3 rounded-md bg-white px-2 py-1 text-center shadow-md">
                                <p class="font-display text-base font-extrabold leading-none text-text-main">
                                    {{ new Date(ev.date_debut).getDate().toString().padStart(2, '0') }}
                                </p>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-text-sub">
                                    {{ new Date(ev.date_debut).toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase().replace('.', '') }}
                                </p>
                            </div>
                        </div>

                        <div class="p-4">
                            <span :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurType(ev.type?.code)]">
                                {{ ev.type?.nom }}
                            </span>
                            <h3 class="mt-2 font-display text-base font-extrabold leading-tight text-text-main line-clamp-2">
                                {{ ev.titre }}
                            </h3>
                            <p v-if="ev.lieu" class="mt-1 text-xs text-text-sub">
                                {{ ev.lieu }}
                            </p>
                            <p class="mt-3 text-xs font-bold text-moov-blue">
                                S'inscrire →
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <div v-else-if="role === 'responsable_dcirp'">

            <!-- En-tête -->
            <div class="mb-8">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    {{ dateAujourdhui }}
                </p>
                <h1 class="mt-1 font-display text-3xl font-extrabold text-text-main">
                    Bonjour, {{ userPrenom }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Pilotage stratégique de la responsabilité sociale d'entreprise
                </p>
            </div>

            <!-- KPIs OPÉRATIONNELS -->
            <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Link href="/evenements"
                      class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Événements en cours
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-blue-600">
                        {{ kpis.evenements_en_cours }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Suivi actif</p>
                </Link>

                <Link href="/inscriptions?statut=en_attente"
                      :class="['rounded-xl p-5 shadow-card transition hover:shadow-card-hover',
                          kpis.dossiers_a_valider > 0
                              ? 'bg-amber-50 border-2 border-amber-300'
                              : 'bg-card']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Dossiers à valider
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold"
                       :class="kpis.dossiers_a_valider > 0 ? 'text-amber-600' : 'text-text-muted'">
                        {{ kpis.dossiers_a_valider }}
                    </p>
                    <p class="mt-1 text-xs"
                       :class="kpis.dossiers_a_valider > 0 ? 'text-amber-700 font-bold' : 'text-text-sub'">
                        {{ kpis.dossiers_a_valider > 0 ? 'Action requise →' : 'Tout est à jour' }}
                    </p>
                </Link>

                <div class="rounded-xl bg-card p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Taux de présence
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">
                        {{ kpis.taux_presence }}<span class="text-base">%</span>
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Confirmés présents</p>
                </div>

                <div class="rounded-xl bg-card p-5 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Bénéficiaires
                    </p>
                    <p class="mt-2 font-display text-3xl font-extrabold text-moov-orange">
                        {{ kpis.beneficiaires }}
                    </p>
                    <p class="mt-1 text-xs text-text-sub">Personnes touchées</p>
                </div>
            </div>

            <!-- ── INDICATEURS RSE STRATÉGIQUES ── -->
            <div class="mb-8 rounded-2xl bg-gradient-to-br from-moov-blue to-moov-blue-dark p-6 text-white">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-display text-lg font-extrabold">Indicateurs RSE</h2>
                        <p class="text-xs text-white/70">Performance globale de la stratégie</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <!-- Bénéficiaires directs -->
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">
                            Bénéficiaires directs
                        </p>
                        <p class="mt-2 font-display text-2xl font-extrabold">
                            {{ statsRSE?.beneficiaires_directs ?? 0 }}
                        </p>
                    </div>

                    <!-- Taux femmes -->
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">
                            Femmes bénéficiaires
                        </p>
                        <p class="mt-2 font-display text-2xl font-extrabold">
                            {{ statsRSE?.taux_femmes ?? 0 }}<span class="text-sm">%</span>
                        </p>
                        <p class="mt-1 text-[10px] text-white/70">
                            Cible : {{ statsRSE?.cible_femmes ?? 60 }}%
                        </p>
                    </div>

                    <!-- Associations -->
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">
                            Associations soutenues
                        </p>
                        <p class="mt-2 font-display text-2xl font-extrabold">
                            {{ statsRSE?.associations_soutenues ?? 0 }}
                        </p>
                    </div>

                    <!-- Budget engagé -->
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">
                            Budget engagé
                        </p>
                        <p class="mt-2 font-display text-xl font-extrabold">
                            {{ Number(statsRSE?.budget_engage ?? 0).toLocaleString('fr-FR') }}
                            <span class="text-xs">F</span>
                        </p>
                        <p class="mt-1 text-[10px] text-white/70">
                            {{ statsRSE?.taux_budget ?? 0 }}% de l'enveloppe
                        </p>
                    </div>
                </div>
            </div>

            <!-- ════ CAMEMBERT - Performance par type d'événement ════ -->
            <div class="mb-8 rounded-xl bg-card p-6 shadow-card">
                <div class="mb-5">
                    <h2 class="font-display text-base font-bold text-text-main">
                        Répartition par type d'événement
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">
                        Volumétrie par catégorie d'événement
                    </p>
                </div>

                <div v-if="totalPerformance > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-center">
                    <!-- Camembert -->
                    <div class="relative h-64">
                        <Doughnut :data="chartDataPerformance" :options="chartOptions" />
                        <!-- Total au centre -->
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <p class="font-display text-3xl font-extrabold text-text-main">
                                {{ totalPerformance }}
                            </p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Au total
                            </p>
                        </div>
                    </div>

                    <!-- Légende détaillée -->
                    <div class="space-y-2.5">
                        <div v-for="p in performanceTypes" :key="p.code"
                             class="flex items-center gap-3">
                            <span class="h-3 w-3 flex-shrink-0 rounded-full"
                                  :style="{ backgroundColor: couleursTypeHex[p.code] || '#94a3b8' }"/>
                            <span class="flex-1 truncate text-sm text-text-sub">{{ p.type }}</span>
                            <span class="text-xs text-text-muted">
                                {{ totalPerformance > 0 ? Math.round((p.total / totalPerformance) * 100) : 0 }}%
                            </span>
                            <span :class="['font-display text-lg font-extrabold w-8 text-right',
                                p.total > 0 ? 'text-text-main' : 'text-text-muted']">
                                {{ p.total }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-10 text-center text-sm text-text-sub">
                    Aucun événement à analyser
                </div>
            </div>

            <!-- Derniers événements actifs -->
            <div class="rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-display text-base font-bold text-text-main">
                                Événements actifs
                            </h2>
                            <p class="mt-1 text-xs text-text-sub">
                                Publiés ou en cours
                            </p>
                        </div>
                        <Link href="/evenements"
                              class="text-xs font-bold text-moov-orange hover:text-moov-orange-dark">
                            Voir tous →
                        </Link>
                    </div>
                </div>

                <div v-if="derniersEvenements?.length" class="divide-y divide-border-soft">
                    <Link v-for="ev in derniersEvenements" :key="ev.id"
                          :href="`/evenements/${ev.id}`"
                          class="flex items-start gap-3 p-4 transition hover:bg-page-bg/50">
                        <div class="flex h-12 w-12 flex-shrink-0 flex-col items-center justify-center rounded-lg bg-page-bg">
                            <p class="font-display text-base font-extrabold leading-none text-text-main">
                                {{ ev.date_debut ? new Date(ev.date_debut).getDate().toString().padStart(2, '0') : '--' }}
                            </p>
                            <p class="text-[10px] font-bold uppercase text-text-sub">
                                {{ ev.date_debut
                                    ? new Date(ev.date_debut).toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase().replace('.', '')
                                    : '---' }}
                            </p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-1 font-bold text-text-main">{{ ev.titre }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-text-sub">
                                <span v-if="ev.lieu">{{ ev.lieu }}</span>
                                <span v-if="ev.lieu && ev.type">·</span>
                                <span v-if="ev.type"
                                      :class="['rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                          couleurType(ev.type.code)]">
                                    {{ ev.type.nom }}
                                </span>
                            </div>
                        </div>
                        <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                            couleurStatutEvenement(ev.statut)]">
                            {{ ev.statut.replace('_', ' ') }}
                        </span>
                    </Link>
                </div>

                <div v-else class="p-10 text-center text-sm text-text-sub">
                    Aucun événement actif
                </div>
            </div>
        </div>

        
        <div v-else class="rounded-xl bg-card p-12 text-center shadow-card">
            <p class="font-display text-lg font-extrabold text-text-main">
                Tableau de bord {{ role }}
            </p>
            <p class="mt-2 text-sm text-text-sub">
                Cette vue sera adaptée dans les prochaines étapes (à venir).
            </p>
        </div>

    </DashboardLayout>
</template>