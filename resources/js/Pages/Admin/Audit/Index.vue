<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    activities:           { type: Object, required: true },
    stats:                { type: Object, required: true },
    logNamesDisponibles:  { type: Array, default: () => [] },
    eventsDisponibles:    { type: Array, default: () => [] },
    causersDisponibles:   { type: Array, default: () => [] },
    filters:              { type: Object, default: () => ({}) },
})

// ─── FILTRES ────────────────────────────────────────
const localFilters = reactive({
    log_name:       props.filters.log_name ?? '',
    event:          props.filters.event ?? '',
    causer_id:      props.filters.causer_id ?? '',
    debut:          props.filters.debut ?? '',
    fin:            props.filters.fin ?? '',
    search:         props.filters.search ?? '',
    important_only: props.filters.important_only ?? true,
})

let debounceTimer
const appliquerFiltres = () => {
    router.get('/admin/audit', localFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

// Auto-apply en debounce pour la recherche texte
watch(() => localFilters.search, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(appliquerFiltres, 400)
})

const reinitialiser = () => {
    localFilters.log_name = ''
    localFilters.event = ''
    localFilters.causer_id = ''
    localFilters.debut = ''
    localFilters.fin = ''
    localFilters.search = ''
    localFilters.important_only = true
    router.get('/admin/audit', { important_only: true })
}

// ─── MODALE DÉTAIL ──────────────────────────────────
const detailOuvert = ref(false)
const detailLoading = ref(false)
const activiteDetail = ref(null)

const ouvrirDetail = async (id) => {
    detailOuvert.value = true
    detailLoading.value = true
    activiteDetail.value = null

    try {
        const response = await fetch(`/admin/audit/${id}`, {
            headers: { 'Accept': 'application/json' },
        })
        const data = await response.json()
        activiteDetail.value = data.activity
    } catch (e) {
        console.error('Erreur chargement détail :', e)
    } finally {
        detailLoading.value = false
    }
}

// ─── EXPORT CSV ─────────────────────────────────────
const exporterCsv = () => {
    const params = new URLSearchParams()
    Object.entries(localFilters).forEach(([k, v]) => {
        if (v && v !== '') params.append(k, v)
    })
    window.location.href = `/admin/audit/export?${params.toString()}`
}

// ─── HELPERS LIBELLÉS ───────────────────────────────
const labelLogName = (name) => ({
    user:        'Utilisateur',
    evenement:   'Événement',
    inscription: 'Inscription',
    paiement:    'Paiement',
    request:     'Requête HTTP',
}[name] || name)

const couleurLogName = (name) => ({
    user:        { bg: 'bg-blue-50',    text: 'text-blue-700',    border: 'border-blue-200' },
    evenement:   { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200' },
    inscription: { bg: 'bg-orange-50',  text: 'text-orange-700',  border: 'border-orange-200' },
    paiement:    { bg: 'bg-violet-50',  text: 'text-violet-700',  border: 'border-violet-200' },
    request:     { bg: 'bg-slate-50',   text: 'text-slate-700',   border: 'border-slate-200' },
}[name] || { bg: 'bg-slate-50', text: 'text-slate-700', border: 'border-slate-200' })

const labelEvent = (event) => ({
    created: 'Création',
    updated: 'Modification',
    deleted: 'Suppression',
    restored: 'Restauration',
    post:    'Soumission',
    get:     'Consultation',
    put:     'Mise à jour',
    patch:   'Modification',
    delete:  'Suppression',
}[event] || event)

const couleurEvent = (event) => ({
    created:  { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    updated:  { bg: 'bg-blue-100',    text: 'text-blue-700',    dot: 'bg-blue-500' },
    deleted:  { bg: 'bg-red-100',     text: 'text-red-700',     dot: 'bg-red-500' },
    restored: { bg: 'bg-violet-100',  text: 'text-violet-700',  dot: 'bg-violet-500' },
    post:     { bg: 'bg-violet-100',  text: 'text-violet-700',  dot: 'bg-violet-500' },
    get:      { bg: 'bg-slate-100',   text: 'text-slate-700',   dot: 'bg-slate-400' },
    put:      { bg: 'bg-blue-100',    text: 'text-blue-700',    dot: 'bg-blue-500' },
    patch:    { bg: 'bg-blue-100',    text: 'text-blue-700',    dot: 'bg-blue-500' },
    delete:   { bg: 'bg-red-100',     text: 'text-red-700',     dot: 'bg-red-500' },
}[event] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400' })

const nomCauser = (causer) => {
    if (!causer) return 'Système'
    if (causer.prenom && causer.nom) return `${causer.prenom} ${causer.nom}`
    if (causer.email) return causer.email
    return `Utilisateur #${causer.id}`
}

const initialesCauser = (causer) => {
    if (!causer) return 'SYS'
    return `${(causer.prenom?.[0] ?? '')}${(causer.nom?.[0] ?? '')}`.toUpperCase() || 'U'
}

const formatDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const formatValue = (v) => {
    if (v === null || v === undefined) return '—'
    if (typeof v === 'boolean') return v ? '✓ Oui' : '✗ Non'
    if (typeof v === 'object') return JSON.stringify(v)
    return String(v)
}

// ─── ACTIONS COMPACTES ──────────────────────────────
const aDesChangements = (activity) => activity.has_changes ?? false
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Administration
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    Journal d'activité
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Traçabilité chronologique de toutes les actions sur la plateforme
                </p>
            </div>
            <button @click="exporterCsv"
                    class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter CSV
            </button>
        </div>

        <!-- ── KPIs ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total activités</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Aujourd'hui</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ stats.aujourdhui }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Cette semaine</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-orange-600">{{ stats.cette_semaine }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Utilisateurs actifs</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-violet-600">{{ stats.causers_actifs }}</p>
            </div>
        </div>

        <!-- ── FILTRES ── -->
        <div class="mb-6 rounded-xl bg-card p-5 shadow-card">
            <div class="flex flex-wrap items-end gap-3">

                <!-- Type -->
                <div class="flex-1 min-w-[140px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Type</label>
                    <select v-model="localFilters.log_name" @change="appliquerFiltres"
                            class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                        <option value="">Tous les types</option>
                        <option v-for="ln in logNamesDisponibles" :key="ln" :value="ln">
                            {{ labelLogName(ln) }}
                        </option>
                    </select>
                </div>

                <!-- Action -->
                <div class="flex-1 min-w-[140px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Action</label>
                    <select v-model="localFilters.event" @change="appliquerFiltres"
                            class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                        <option value="">Toutes les actions</option>
                        <option v-for="ev in eventsDisponibles" :key="ev" :value="ev">
                            {{ labelEvent(ev) }}
                        </option>
                    </select>
                </div>

                <!-- Auteur -->
                <div class="flex-1 min-w-[180px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Auteur</label>
                    <select v-model="localFilters.causer_id" @change="appliquerFiltres"
                            class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                        <option value="">Tous les auteurs</option>
                        <option v-for="u in causersDisponibles" :key="u.id" :value="u.id">
                            {{ u.prenom }} {{ u.nom }}
                        </option>
                    </select>
                </div>

                <!-- Période -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Du</label>
                    <input v-model="localFilters.debut" type="date" @change="appliquerFiltres"
                           class="rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Au</label>
                    <input v-model="localFilters.fin" type="date" @change="appliquerFiltres"
                           class="rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <!-- Recherche -->
                <div class="flex-1 min-w-[200px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">Recherche</label>
                    <input v-model="localFilters.search" type="search" placeholder="Description, sujet..."
                           class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <!-- Reset -->
                <div>
                    <button @click="reinitialiser"
                            class="rounded-lg border border-border-soft bg-white px-4 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Checkbox actions importantes -->
            <div class="mt-3 flex items-center gap-2 border-t border-border-soft pt-3">
                <input id="important_only" type="checkbox" v-model="localFilters.important_only" @change="appliquerFiltres"
                       class="rounded border-slate-300 text-moov-blue focus:ring-moov-blue"/>
                <label for="important_only" class="text-xs font-semibold text-text-sub cursor-pointer">
                    Actions importantes uniquement (cache les consultations GET)
                </label>
            </div>
        </div>

        <!-- ── TABLEAU + TIMELINE ── -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr,1fr]">

            <!-- ─── TABLEAU ─── -->
            <div class="overflow-hidden rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-4">
                    <h2 class="text-sm font-bold text-text-main">
                        {{ activities.total ?? activities.data.length }} activité(s) trouvée(s)
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-soft text-sm">
                        <thead class="bg-page-bg/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Auteur</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Sujet</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft bg-card">
                            <tr v-for="a in activities.data" :key="a.id"
                                class="cursor-pointer transition hover:bg-page-bg/50"
                                @click="ouvrirDetail(a.id)">

                                <!-- Date -->
                                <td class="px-4 py-3 text-text-sub whitespace-nowrap">
                                    <p class="font-medium">{{ formatDate(a.created_at) }}</p>
                                    <p class="text-xs text-text-muted">{{ a.created_at_human }}</p>
                                </td>

                                <!-- Auteur -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                             :class="a.causer ? 'bg-moov-blue text-white' : 'bg-slate-200 text-slate-600'">
                                            {{ initialesCauser(a.causer) }}
                                        </div>
                                        <span class="text-text-main font-medium">{{ nomCauser(a.causer) }}</span>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                                       couleurLogName(a.log_name).bg, couleurLogName(a.log_name).text]">
                                            {{ labelLogName(a.log_name) }}
                                        </span>
                                        <span :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                                       couleurEvent(a.event).bg, couleurEvent(a.event).text]">
                                            {{ labelEvent(a.event) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-text-main">{{ a.description }}</p>
                                </td>

                                <!-- Sujet -->
                                <td class="px-4 py-3">
                                    <div v-if="a.subject">
                                        <p class="text-xs text-text-muted">{{ a.subject.type }}</p>
                                        <p class="font-medium text-text-main">{{ a.subject.label }}</p>
                                    </div>
                                    <span v-else class="text-xs text-text-muted">—</span>
                                </td>

                                <!-- Action chevron -->
                                <td class="px-4 py-3 text-right">
                                    <svg class="h-4 w-4 text-text-muted inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Vide -->
                    <div v-if="!activities.data?.length" class="px-6 py-12 text-center">
                        <p class="font-bold text-text-main">Aucune activité trouvée</p>
                        <p class="mt-1 text-sm text-text-sub">Modifiez les filtres pour voir d'autres activités</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="activities.links?.length > 3"
                     class="flex flex-wrap items-center justify-center gap-1 border-t border-border-soft p-4">
                    <template v-for="link in activities.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                              preserve-scroll preserve-state
                              :class="['rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                  link.active
                                      ? 'bg-moov-blue text-white'
                                      : 'border border-border-soft bg-white text-text-sub hover:border-moov-blue/30']"/>
                        <span v-else v-html="link.label"
                              class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs text-text-muted"/>
                    </template>
                </div>
            </div>

            <!-- ─── TIMELINE ─── -->
            <div class="rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-4">
                    <h2 class="text-sm font-bold text-text-main">Timeline visuelle</h2>
                    <p class="mt-0.5 text-xs text-text-sub">{{ activities.data?.length ?? 0 }} activités récentes</p>
                </div>
                <div class="max-h-[800px] overflow-y-auto p-5">
                    <div v-if="!activities.data?.length" class="py-10 text-center">
                        <p class="text-sm text-text-muted">Aucune activité à afficher</p>
                    </div>

                    <div v-else class="relative space-y-3">
                        <!-- Ligne verticale -->
                        <span class="absolute left-[7px] top-2 bottom-2 w-px bg-border-soft"/>

                        <div v-for="a in activities.data" :key="`tl-${a.id}`"
                             class="relative cursor-pointer pl-8 transition hover:opacity-80"
                             @click="ouvrirDetail(a.id)">

                            <!-- Point -->
                            <span class="absolute left-0 top-3 h-4 w-4 rounded-full ring-4 ring-white"
                                  :class="couleurEvent(a.event).dot"/>

                            <!-- Contenu -->
                            <div class="rounded-lg border bg-white p-3 transition hover:shadow-card"
                                 :class="couleurLogName(a.log_name).border">

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span :class="['inline-block rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                   couleurLogName(a.log_name).bg, couleurLogName(a.log_name).text]">
                                        {{ labelLogName(a.log_name) }}
                                    </span>
                                    <span :class="['inline-block rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                   couleurEvent(a.event).bg, couleurEvent(a.event).text]">
                                        {{ labelEvent(a.event) }}
                                    </span>
                                </div>

                                <p class="mt-1.5 text-sm font-medium text-text-main">{{ a.description }}</p>

                                <p v-if="a.subject" class="mt-1 text-xs text-text-sub">
                                    {{ a.subject.type }} : <span class="font-semibold">{{ a.subject.label }}</span>
                                </p>

                                <p class="mt-1.5 text-xs text-text-muted">
                                    {{ nomCauser(a.causer) }} · {{ a.created_at_human }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--   MODALE DÉTAIL D'UNE ACTIVITÉ                          -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-if="detailOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="detailOuvert = false">

            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-card shadow-2xl">

                <!-- En-tête -->
                <div class="sticky top-0 z-10 flex items-start justify-between border-b border-border-soft bg-card p-5">
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-text-main">
                            Détail de l'activité
                        </h3>
                        <p v-if="activiteDetail" class="mt-0.5 text-xs text-text-sub">
                            ID #{{ activiteDetail.id }} · {{ formatDate(activiteDetail.created_at) }}
                        </p>
                    </div>
                    <button @click="detailOuvert = false"
                            class="rounded-lg p-1 text-text-sub hover:bg-page-bg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Chargement -->
                <div v-if="detailLoading" class="px-6 py-12 text-center text-sm text-text-muted">
                    Chargement...
                </div>

                <!-- Contenu -->
                <div v-else-if="activiteDetail" class="space-y-5 p-5">

                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="['inline-block rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider',
                                       couleurLogName(activiteDetail.log_name).bg,
                                       couleurLogName(activiteDetail.log_name).text]">
                            {{ labelLogName(activiteDetail.log_name) }}
                        </span>
                        <span :class="['inline-block rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider',
                                       couleurEvent(activiteDetail.event).bg,
                                       couleurEvent(activiteDetail.event).text]">
                            {{ labelEvent(activiteDetail.event) }}
                        </span>
                    </div>

                    <!-- Description -->
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Description</p>
                        <p class="mt-1 text-text-main">{{ activiteDetail.description }}</p>
                    </div>

                    <!-- Auteur -->
                    <div v-if="activiteDetail.causer">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Auteur</p>
                        <div class="mt-2 flex items-center gap-3 rounded-lg bg-page-bg/50 p-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-moov-blue text-sm font-bold text-white">
                                {{ initialesCauser(activiteDetail.causer) }}
                            </div>
                            <div>
                                <p class="font-bold text-text-main">{{ nomCauser(activiteDetail.causer) }}</p>
                                <p v-if="activiteDetail.causer.email" class="text-xs text-text-sub">
                                    {{ activiteDetail.causer.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sujet -->
                    <div v-if="activiteDetail.subject">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Sujet</p>
                        <div class="mt-2 rounded-lg bg-page-bg/50 p-3">
                            <p class="text-xs text-text-muted">{{ activiteDetail.subject.type }}</p>
                            <p class="font-bold text-text-main">{{ activiteDetail.subject.label }}</p>
                        </div>
                    </div>

                    <!-- Changements (avant/après) -->
                    <div v-if="activiteDetail.changes?.length">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">
                            Changements ({{ activiteDetail.changes.length }})
                        </p>
                        <div class="mt-2 space-y-2">
                            <div v-for="change in activiteDetail.changes" :key="change.field"
                                 class="rounded-lg border border-border-soft p-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-sub">
                                    {{ change.field }}
                                </p>
                                <div class="mt-1.5 flex items-center gap-2 text-sm">
                                    <span class="rounded bg-red-50 px-2 py-1 text-red-700 line-through">
                                        {{ formatValue(change.old) }}
                                    </span>
                                    <svg class="h-4 w-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                    <span class="rounded bg-emerald-50 px-2 py-1 font-bold text-emerald-700">
                                        {{ formatValue(change.new) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Propriétés brutes (technique) -->
                    <details v-if="activiteDetail.properties && Object.keys(activiteDetail.properties).length"
                             class="rounded-lg border border-border-soft">
                        <summary class="cursor-pointer p-3 text-xs font-bold uppercase tracking-wider text-text-sub hover:bg-page-bg/50">
                            Propriétés techniques (JSON)
                        </summary>
                        <pre class="overflow-x-auto bg-slate-900 p-4 text-xs text-slate-100 rounded-b-lg">{{ JSON.stringify(activiteDetail.properties, null, 2) }}</pre>
                    </details>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>