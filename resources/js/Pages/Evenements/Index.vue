<script setup>
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenements: Object,
    typesEvenement: Array,
    statuts: Array,
    stats: Object,
    filters: Object,
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => user.value?.roles ?? [])

const estStaff = computed(() =>
    roles.value.some(r => ['admin', 'responsable_dcirp', 'organisateur'].includes(r))

)
const userRoles = computed(() => page.props.auth?.user?.roles?.map(r => r.name) ?? [])
const estResponsable = computed(() =>
    (userRoles.value.includes('responsable_dcirp') || userRoles.value.includes('admin')))
const Layout = computed(() => (estStaff.value ? DashboardLayout : PublicLayout))
const peutSInscrire = computed(() =>
    !user.value || roles.value.includes('participant') || roles.value.length === 0
)

const filtreTypeId = ref(props.filters?.type ?? '')
const filtreStatut = ref(props.filters?.statut ?? '')
const recherche = ref(props.filters?.search ?? '')

const evenementsAffiches = computed(() => {
    if (!props.evenements?.data) return []
    let liste = [...props.evenements.data]
    if (filtreTypeId.value) {
        liste = liste.filter(e => e.type_evenement?.id == filtreTypeId.value)
    }
    if (filtreStatut.value) {
        liste = liste.filter(e => e.statut === filtreStatut.value)
    }
    if (recherche.value) {
        const q = recherche.value.toLowerCase()
        liste = liste.filter(e =>
            e.titre?.toLowerCase().includes(q) ||
            e.lieu?.nom?.toLowerCase().includes(q)
        )
    }
    return liste
})

const filtrer = () => {
    router.get('/evenements', {
        type: filtreTypeId.value || undefined,
        statut: filtreStatut.value || undefined,
        search: recherche.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}

// ── COULEURS PAR TYPE (sobres et professionnelles) ──
const couleurType = (code) => ({
    BARA_MOUSSO: { bg: 'bg-amber-50', text: 'text-amber-700', accent: 'bg-amber-600', label: 'Concours' },
    CONF: { bg: 'bg-rose-50', text: 'text-rose-700', accent: 'bg-rose-600', label: 'Conférence' },
    SPORT: { bg: 'bg-blue-50', text: 'text-blue-700', accent: 'bg-blue-600', label: 'Tournoi' },
    CHALLENGE: { bg: 'bg-violet-50', text: 'text-violet-700', accent: 'bg-violet-600', label: 'Challenge' },
    FORMATION: { bg: 'bg-emerald-50', text: 'text-emerald-700', accent: 'bg-emerald-600', label: 'Formation' },
    HACK: { bg: 'bg-orange-50', text: 'text-orange-700', accent: 'bg-orange-600', label: 'Hackathon' },
    SALON: { bg: 'bg-indigo-50', text: 'text-indigo-700', accent: 'bg-indigo-600', label: 'Salon' },
    ATELIER: { bg: 'bg-teal-50', text: 'text-teal-700', accent: 'bg-teal-600', label: 'Atelier' },
    WEBINAIRE: { bg: 'bg-purple-50', text: 'text-purple-700', accent: 'bg-purple-600', label: 'Webinaire' },
}[code] || { bg: 'bg-slate-50', text: 'text-slate-700', accent: 'bg-slate-600', label: 'Événement' })

const couleurStatut = (statut) => ({
    publie: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publié' },
    en_cours: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', label: 'En cours' },
    termine: { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: 'Terminé' },
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    annule: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const formaterMoisJour = (d) => {
    if (!d) return { jour: '--', mois: '---' }
    const date = new Date(d)
    return {
        jour: date.getDate().toString().padStart(2, '0'),
        mois: date.toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase().replace('.', ''),
    }
}

const lienInscription = (ev) => {
    if (!user.value) return '/login'
    return `/evenements/${ev.id}/inscrire`
}

const supprimerEvenement = (id) => {
    if (confirm('Archiver cet événement ?')) {
        router.delete(`/evenements/${id}`)
    }
}

const publierEvenement = (id) => {
    if (confirm('Valider et publier cet événement ?')) {
        router.post(`/evenements/${id}/valider`, {}, {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <component :is="Layout">


        <template v-if="estStaff">

            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-text-main">
                        Gestion Événements
                    </h1>
                    <p class="mt-1 text-sm text-text-sub">
                        Planification et suivi opérationnel des typologies Moov
                    </p>
                </div>
                <Link href="/evenements/create"
                    class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    Nouvel Événement 
                </Link>
            </div>

            <!-- KPIs -->
            <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-card p-4 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                        {{ evenements?.total ?? evenementsAffiches.length }}
                    </p>
                </div>
                <div class="rounded-xl bg-card p-4 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Publiés</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">
                        {{evenementsAffiches.filter(e => e.statut === 'publie').length}}
                    </p>
                </div>
                <div class="rounded-xl bg-card p-4 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">En cours</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-blue-600">
                        {{evenementsAffiches.filter(e => e.statut === 'en_cours').length}}
                    </p>
                </div>
                <div class="rounded-xl bg-card p-4 shadow-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Brouillons</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-amber-600">
                        {{evenementsAffiches.filter(e => e.statut === 'brouillon').length}}
                    </p>
                </div>
            </div>

            <!-- Tableau -->
            <div class="overflow-hidden rounded-xl bg-card shadow-card">
                <div class="border-b border-border-soft p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-sm font-bold text-text-main">Événements en cours & à venir</h2>
                        <div class="ml-auto flex flex-wrap items-center gap-2">
                            <select v-model="filtreTypeId" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                                <option value="">Tous les types</option>
                                <option v-for="t in typesEvenement" :key="t.id" :value="t.id">
                                    {{ t.nom }}
                                </option>
                            </select>
                            <select v-model="filtreStatut" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                                <option value="">Tous les statuts</option>
                                <option v-for="s in statuts" :key="s.value" :value="s.value">
                                    {{ s.label }}
                                </option>
                            </select>
                            <input v-model="recherche" @keyup.enter="filtrer" type="search" placeholder="Filtrer..."
                                class="w-48 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue" />
                        </div>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-border-soft text-sm">
                    <thead class="bg-page-bg/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Événement</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Type</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Lieu</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Date</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Statut</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                                Inscrits</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-soft bg-card">
                        <tr v-for="ev in evenementsAffiches" :key="ev.id" class="transition hover:bg-page-bg/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <!-- Pastille de couleur sobre -->
                                    <div
                                        :class="['h-10 w-1 rounded-full', couleurType(ev.type_evenement?.code).accent]" />
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-text-main">{{ ev.titre }}</p>
                                        <p class="text-xs text-text-muted">Réf: EV-{{ String(ev.id).padStart(4, '0') }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span :class="['rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider',
                                    couleurType(ev.type_evenement?.code).bg,
                                    couleurType(ev.type_evenement?.code).text]">
                                    {{ ev.type_evenement?.nom ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-text-sub">{{ ev.lieu?.nom ?? '—' }}</td>
                            <td class="px-5 py-4 text-text-sub">{{ formaterDate(ev.date_debut) }}</td>
                            <td class="px-5 py-4">
                                <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                    couleurStatut(ev.statut).bg, couleurStatut(ev.statut).text]">
                                    <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(ev.statut).dot]" />
                                    {{ couleurStatut(ev.statut).label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-bold text-text-main">{{ ev.inscriptions_count ?? 0 }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/evenements/${ev.id}`"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-moov-blue-50 hover:text-moov-blue"
                                        title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link :href="`/evenements/${ev.id}/edit`"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-amber-50 hover:text-amber-600"
                                        title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button v-if="ev.statut === 'brouillon' && estResponsable"
                                        @click="publierEvenement(ev.id)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-emerald-50 hover:text-emerald-600"
                                        title="Publier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button @click="supprimerEvenement(ev.id)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-red-50 hover:text-red-600"
                                        title="Archiver">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!evenementsAffiches.length" class="px-6 py-12 text-center">
                    <p class="font-bold text-text-main">Aucun événement trouvé</p>
                    <p class="mt-1 text-sm text-text-sub">
                        {{ filtreTypeId || filtreStatut || recherche
                            ? 'Aucun résultat avec ces filtres.'
                            : 'Commencez par créer un événement.' }}
                    </p>
                    <Link href="/evenements/create"
                        class="mt-4 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white">
                        Nouvel Événement +
                    </Link>
                </div>
            </div>

            <div v-if="evenements?.links?.length > 3" class="mt-6 flex justify-center gap-1">
                <template v-for="link in evenements.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                        link.active
                            ? 'bg-moov-blue text-white'
                            : 'border border-border-soft bg-white text-text-sub hover:border-moov-blue/30']" />
                    <span v-else v-html="link.label"
                        class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-sm text-text-muted" />
                </template>
            </div>
        </template>

        <template v-else>

            <!-- HERO sobre -->
            <section class="bg-gradient-to-br from-moov-blue to-moov-blue-dark px-4 py-20">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-moov-orange">
                        Plateforme officielle Moov Africa Burkina · DCIRP
                    </p>
                    <h1 class="mt-5 font-display text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                        Les événements qui font<br>
                        <span class="text-moov-orange">bouger le Burkina</span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-xl text-base text-white/70">
                        Conférences, tournois, formations, hackathons. Tous les événements RSE de Moov Africa, réunis
                        sur une seule plateforme.
                    </p>

                    <div class="mx-auto mt-10 flex max-w-md items-center justify-center gap-12 text-white/80">
                        <div>
                            <p class="font-display text-3xl font-extrabold text-white">{{ stats?.total ?? 0 }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider">Événements</p>
                        </div>
                        <div class="h-10 w-px bg-white/20" />
                        <div>
                            <p class="font-display text-3xl font-extrabold text-white">{{ stats?.en_cours ?? 0 }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider">En cours</p>
                        </div>
                        <div class="h-10 w-px bg-white/20" />
                        <div>
                            <p class="font-display text-3xl font-extrabold text-moov-orange">{{ typesEvenement?.length
                                ?? 0 }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider">Typologies</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FILTRES sobres -->
            <section class="sticky top-16 z-40 border-b border-border-soft bg-white">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex gap-1.5 overflow-x-auto py-4" style="scrollbar-width:none">
                        <button @click="filtreTypeId = ''; filtrer()"
                            :class="['whitespace-nowrap rounded-md border px-4 py-2 text-sm font-semibold transition',
                                !filtreTypeId
                                    ? 'border-moov-noir bg-moov-noir text-white'
                                    : 'border-border-soft bg-white text-text-sub hover:border-moov-noir hover:text-moov-noir']">
                            Tous les événements
                        </button>
                        <button v-for="t in typesEvenement" :key="t.id" @click="filtreTypeId = t.id; filtrer()"
                            :class="['whitespace-nowrap rounded-md border px-4 py-2 text-sm font-semibold transition',
                                filtreTypeId == t.id
                                    ? 'border-moov-noir bg-moov-noir text-white'
                                    : 'border-border-soft bg-white text-text-sub hover:border-moov-noir hover:text-moov-noir']">
                            {{ t.nom }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- GRILLE CARTES sobres -->
            <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

                <p class="mb-8 text-sm text-text-sub">
                    <span class="font-bold text-text-main">{{ evenementsAffiches.length }}</span>
                    événement(s) trouvé(s)
                </p>

                <div v-if="evenementsAffiches.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                    <article v-for="ev in evenementsAffiches" :key="ev.id"
                        class="group overflow-hidden rounded-lg border border-border-soft bg-white transition duration-200 hover:border-moov-blue/30 hover:shadow-card-hover">

                        <!-- Visuel sobre avec date overlay -->
                        <div class="relative h-48 overflow-hidden bg-page-bg">
                            <img v-if="ev.visuel_url" :src="ev.visuel_url" :alt="ev.titre"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />

                            <!-- Placeholder sobre quand pas d'image -->
                            <div v-else
                                :class="['flex h-full w-full items-center justify-center', couleurType(ev.type_evenement?.code).bg]">
                                <div class="text-center">
                                    <p
                                        :class="['font-display text-5xl font-extrabold opacity-30', couleurType(ev.type_evenement?.code).text]">
                                        {{ ev.type_evenement?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Overlay date (à gauche) -->
                            <div class="absolute left-4 top-4 rounded-md bg-white px-3 py-2 text-center shadow-md">
                                <p class="font-display text-2xl font-extrabold leading-none text-moov-noir">
                                    {{ formaterMoisJour(ev.date_debut).jour }}
                                </p>
                                <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-text-sub">
                                    {{ formaterMoisJour(ev.date_debut).mois }}
                                </p>
                            </div>

                            <!-- Type (en haut à droite) -->
                            <span :class="['absolute right-4 top-4 rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider',
                                couleurType(ev.type_evenement?.code).bg,
                                couleurType(ev.type_evenement?.code).text]">
                                {{ ev.type_evenement?.nom }}
                            </span>
                        </div>

                        <!-- Body -->
                        <div class="p-5">
                            <h3
                                class="line-clamp-2 font-display text-base font-extrabold leading-snug text-text-main group-hover:text-moov-blue">
                                {{ ev.titre }}
                            </h3>

                            <!-- Méta-infos -->
                            <div class="mt-4 space-y-2 text-sm text-text-sub">
                                <p class="flex items-start gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-text-muted" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="line-clamp-1">{{ ev.lieu?.nom ?? 'Lieu à définir' }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-text-muted" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>{{ ev.inscriptions_count ?? 0 }} inscrit(s)</span>
                                </p>
                            </div>

                            <!-- Statut + actions -->
                            <div class="mt-5 flex items-center justify-between border-t border-border-soft pt-4">
                                <span :class="['inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider',
                                    couleurStatut(ev.statut).text]">
                                    <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(ev.statut).dot]" />
                                    {{ couleurStatut(ev.statut).label }}
                                </span>

                                <div class="flex gap-2">
                                    <Link :href="`/evenements/${ev.id}`"
                                        class="rounded-md border border-border-soft px-3 py-1.5 text-xs font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                                        Détails
                                    </Link>
                                    <Link :href="`/evenements/${ev.id}`" class="...">
                                        {{ ev.type?.code === 'SALON' ? 'En savoir plus →' : 'S\'inscrire →' }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-lg border border-dashed border-border-soft py-20 text-center">
                    <h3 class="font-display text-lg font-bold text-text-main">Aucun événement disponible</h3>
                    <p class="mt-2 text-sm text-text-sub">
                        {{ filtreTypeId ? "Aucun événement dans cette catégorie." : "Aucun événement publié pour le moment."  }}
                    </p>
                </div>

                <div v-if="evenements?.links?.length > 3" class="mt-12 flex justify-center gap-1.5">
                    <template v-for="link in evenements.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['rounded-md border px-4 py-2 text-sm font-semibold transition',
                            link.active
                                ? 'border-moov-noir bg-moov-noir text-white'
                                : 'border-border-soft bg-white text-text-sub hover:border-moov-noir']" />
                        <span v-else v-html="link.label"
                            class="rounded-md border border-border-soft bg-white px-4 py-2 text-sm text-text-muted" />
                    </template>
                </div>
            </section>
        </template>

    </component>
</template>