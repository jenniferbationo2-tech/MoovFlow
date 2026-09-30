<script setup>
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    evenements: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    types: { type: Array, default: () => [] },
    typesEvenement: { type: Array, default: () => [] }, // backward compat (staff)
    statuts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({ total: 0, en_cours: 0, typologies: { actives: 0, disponibles: 0 } }) },
    filters: { type: Object, default: () => ({}) },
    now: { type: String, default: () => new Date().toISOString() },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => user.value?.roles ?? [])
// Récupération robuste des rôles (gère les 2 formats possibles)
const userRoles = computed(() => {
    const rolesData = page.props.auth?.user?.roles ?? []
    // Si c'est un tableau d'objets [{name: 'admin'}, ...]
    if (rolesData.length > 0 && typeof rolesData[0] === 'object') {
        return rolesData.map(r => r.name)
    }

    return rolesData
})

const estStaff = computed(() =>
    userRoles.value.some(r => ['responsable_dcirp', 'organisateur'].includes(r))
)

// ═══ PERMISSIONS PRÉCISES PAR RÔLE ═══

// Admin pur = supervision uniquement
const estAdminPur = computed(() =>
    userRoles.value.includes('admin') && !userRoles.value.includes('responsable_dcirp')
)

// Responsable dCIRP (sans confondre avec admin)
const estResponsable = computed(() => userRoles.value.includes('responsable_dcirp'))

// Organisateur pur
const estOrganisateurPur = computed(() =>
    userRoles.value.includes('organisateur') &&
    !userRoles.value.includes('admin') &&
    !userRoles.value.includes('responsable_dcirp')
)

// Peut créer un événement = Responsable OU Organisateur (PAS admin)
const peutCreer = computed(() => estResponsable.value || estOrganisateurPur.value)

// Peut modifier un événement spécifique (créateur seulement)
const peutModifierEvent = (ev) => {
    if (estAdminPur.value) return false  // Admin NE PEUT PAS modifier
    const userId = page.props.auth?.user?.id
    return ev.created_by === userId
}

// Peut supprimer/archiver un événement spécifique
const peutSupprimerEvent = (ev) => {
    if (estAdminPur.value) return false  // Admin NE PEUT PAS supprimer
    const userId = page.props.auth?.user?.id
    return ev.created_by === userId
}

// Peut publier (valider) : SEULEMENT responsable, et PAS son propre événement
const peutPublierEvent = (ev) => {
    if (!estResponsable.value) return false
    const userId = page.props.auth?.user?.id
    return ev.created_by !== userId && ['brouillon', 'en_validation'].includes(ev.statut)
}

// HERO SLIDESHOW 
const heroImages = [
    {
        url: '/images/marley.png',
        caption: 'Des conférences qui inspirent et connectent les acteurs du numérique',
    },
    {
        url: '/images/Moov-1024x538.jpg',
        caption: 'Des compétitions sportives qui renforcent la cohésion des équipes Moov',
    },
    {
        url: '/images/prix.png',
        caption: 'Des hackathons pour innover et transformer les idées en solutions',
    },
    {
        url: '/images/Tery.jpg',
        caption: 'Des formations numériques pour développer les talents de demain',
    },
    {
        url: '/images/moov-africa_banner01-scaled.jpg',
        caption: 'Des salons professionnels pour créer des opportunités B2B au Burkina',
    },
     {
        url: '/images/moov-africa_banner02.jpg',
        caption: 'Des salons professionnels pour créer des opportunités B2B au Burkina',
    },

     {
        url: '/images/snc.png',
        caption: 'Des salons professionnels pour créer des opportunités B2B au Burkina',
    },
]

const heroIndex = ref(0)
const heroPause = ref(false)

const allerHeroImage = (i) => {
    heroIndex.value = i
}

const heroPrecedent = () => {
    heroIndex.value = (heroIndex.value - 1 + heroImages.length) % heroImages.length
}

const heroSuivant = () => {
    heroIndex.value = (heroIndex.value + 1) % heroImages.length
}

// Défilement automatique toutes les 5 secondes
let heroInterval = null

const demarrerHero = () => {
    heroInterval = setInterval(() => {
        if (!heroPause.value) {
            heroIndex.value = (heroIndex.value + 1) % heroImages.length
        }
    }, 5000)
}

import { onMounted, onUnmounted } from 'vue'

onMounted(() => demarrerHero())
onUnmounted(() => clearInterval(heroInterval))

const Layout = computed(() => (estStaff.value ? DashboardLayout : PublicLayout))

const peutSInscrire = computed(() =>
    !user.value || userRoles.value.includes('participant') || userRoles.value.length === 0
)

const filtreTypeId = ref(props.filters?.type ?? '')
const filtreStatut = ref(props.filters?.statut ?? '')
const recherche = ref(props.filters?.search ?? '')

const evenementsAffiches = computed(() => {
    if (!props.evenements?.data) return []
    let liste = [...props.evenements.data]
    if (filtreTypeId.value && filtreTypeId.value !== '') {
        liste = liste.filter(e => e.type_evenement?.code === filtreTypeId.value)
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
    archive: { bg: 'bg-indigo-50', text: 'text-indigo-700', dot: 'bg-indigo-400', label: 'Archivé' },
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    annule: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Annulé' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const dateNow = computed(() => new Date(props.now))

const statutDynamique = (ev) => {
    if (ev.statut === 'annule') return 'annule'
    if (ev.statut === 'archive') return 'archive'
    if (ev.statut === 'brouillon' || ev.statut === 'en_validation') return ev.statut

    const debut = ev.date_debut ? new Date(ev.date_debut) : null
    const fin = ev.date_fin ? new Date(ev.date_fin) : null
    const now = dateNow.value

    if (fin && fin < now) return 'termine'
    if (debut && fin && debut <= now && now <= fin) return 'en_cours'
    if (debut && debut > now) return 'a_venir'

    return ev.statut || 'publie'
}

const couleurStatutDyn = (statutDyn) => ({
    a_venir:   { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'À venir' },
    en_cours:  { bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500',    label: 'En cours' },
    termine:   { bg: 'bg-slate-100',  text: 'text-slate-600',   dot: 'bg-slate-400',   label: 'Terminé' },
    archive:   { bg: 'bg-indigo-50',  text: 'text-indigo-700',  dot: 'bg-indigo-400',  label: 'Archivé' },
    annule:    { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     label: 'Annulé' },
    brouillon: { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   label: 'Brouillon' },
}[statutDyn] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statutDyn })


const actionEvenement = (ev) => {
    const sd = statutDynamique(ev)

    if (ev.deja_inscrit && (sd === 'a_venir' || sd === 'publie')) {
        return { label: 'Déjà inscrit ✓', href: null, style: 'disabled' }
    }
    if (sd === 'a_venir' && peutSInscrire.value && ev.type_evenement?.code !== 'SALON') {
        return { label: "S'inscrire →", href: lienInscription(ev), style: 'primary' }
    }
    if (sd === 'en_cours') {
        return { label: "En cours", href: null, style: 'disabled' }
    }
    if (sd === 'termine') {
        return { label: "Voir détails", href: `/evenements/${ev.id}`, style: 'secondary' }
    }
    if (sd === 'annule') {
        return { label: "Annulé", href: null, style: 'disabled' }
    }
    if (ev.type_evenement?.code === 'SALON') {
        return { label: "En savoir plus →", href: `/evenements/${ev.id}`, style: 'primary' }
    }
    return { label: "Voir détails", href: `/evenements/${ev.id}`, style: 'secondary' }
}


const formaterPrix = (ev) => {
    if (!ev.tarifs || ev.tarifs.length === 0) return 'Gratuit'
    const tarifsPayants = ev.tarifs.filter(t => Number(t.montant) > 0)
    if (tarifsPayants.length === 0) return 'Gratuit'
    const min = Math.min(...tarifsPayants.map(t => Number(t.montant)))
    return `${min.toLocaleString('fr-FR')} FCFA`
}


const typesDisponibles = computed(() => props.types?.length ? props.types : (props.typesEvenement ?? []))

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
    const urlPreinscription = `/evenements/${ev.id}/preinscrire`
    if (!user.value) {
        // Mémorise la page de préinscription visée pour y revenir juste après connexion
        return `/login?redirect=${encodeURIComponent(urlPreinscription)}`
    }
    return urlPreinscription
}

const modalConfirm = ref({
    show: false,
    type: 'default',
    title: '',
    message: '',
    confirmText: 'Confirmer',
    action: null,
})

const ouvrirModalConfirm = (config) => {
    modalConfirm.value = { ...modalConfirm.value, ...config, show: true }
}

const fermerModal = () => {
    modalConfirm.value.show = false
}

const executerAction = () => {
    if (modalConfirm.value.action) {
        modalConfirm.value.action()
    }
    fermerModal()
}

const supprimerEvenement = (id) => {
    ouvrirModalConfirm({
        type: 'danger',
        title: 'Archiver cet événement ?',
        message: 'L\'événement sera retiré de la liste publique. Cette action est réversible par un administrateur.',
        confirmText: 'Oui, archiver',
        action: () => router.delete(`/evenements/${id}`)
    })
}

const publierEvenement = (id) => {
    ouvrirModalConfirm({
        type: 'success',
        title: 'Valider et publier ?',
        message: 'L\'événement sera publié immédiatement et visible publiquement.',
        confirmText: 'Valider et publier',
        action: () => router.post(`/evenements/${id}/valider`, {}, { preserveScroll: true })
    })
}

// ── Groupement des événements par type (vue staff) ──
const ordreTypes = ['BARA_MOUSSO', 'CONF', 'FORMATION', 'HACK', 'CHALLENGE', 'SPORT', 'SALON']

const evenementsParType = computed(() => {
    const groupes = {}
    // Initialiser dans l'ordre souhaité
    ordreTypes.forEach(code => {
        groupes[code] = { label: couleurType(code).label, events: [] }
    })
    // Répartir les événements
    evenementsAffiches.value.forEach(ev => {
        const code = ev.type_evenement?.code ?? 'AUTRE'
        if (!groupes[code]) groupes[code] = { label: couleurType(code).label, events: [] }
        groupes[code].events.push(ev)
    })
    // Trier chaque groupe par date de début
    Object.values(groupes).forEach(g => {
        g.events.sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut))
    })
    return groupes
})
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
                <Link v-if="!estAdminPur" href="/evenements/create"
                    class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    Nouvel Événement
                </Link>
                <span v-else class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-600">
                    Mode supervision
                </span>
            </div>

            <!-- KPIs (cliquables → filtrent la liste) -->
            <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
                <button type="button" @click="filtreStatut = ''; filtrer()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        !filtreStatut ? 'bg-moov-noir ring-2 ring-moov-noir' : 'bg-card']">
                    <p :class="['text-xs font-bold uppercase tracking-wider', !filtreStatut ? 'text-white/60' : 'text-text-muted']">Total</p>
                    <p :class="['mt-2 font-display text-2xl font-extrabold', !filtreStatut ? 'text-white' : 'text-moov-blue']">
                        {{ kpis.total ?? 0 }}
                    </p>
                </button>
                <button type="button" @click="filtreStatut = 'publie'; filtrer()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'publie' ? 'bg-emerald-600 ring-2 ring-emerald-600' : 'bg-card']">
                    <p :class="['text-xs font-bold uppercase tracking-wider', filtreStatut === 'publie' ? 'text-white/70' : 'text-text-muted']">Publiés</p>
                    <p :class="['mt-2 font-display text-2xl font-extrabold', filtreStatut === 'publie' ? 'text-white' : 'text-emerald-600']">
                        {{ kpis.publie ?? 0 }}
                    </p>
                </button>
                <button type="button" @click="filtreStatut = 'en_cours'; filtrer()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'en_cours' ? 'bg-blue-600 ring-2 ring-blue-600' : 'bg-card']">
                    <p :class="['text-xs font-bold uppercase tracking-wider', filtreStatut === 'en_cours' ? 'text-white/70' : 'text-text-muted']">En cours</p>
                    <p :class="['mt-2 font-display text-2xl font-extrabold', filtreStatut === 'en_cours' ? 'text-white' : 'text-blue-600']">
                        {{ kpis.en_cours ?? 0 }}
                    </p>
                </button>
                <button type="button" @click="filtreStatut = 'brouillon'; filtrer()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'brouillon' ? 'bg-amber-500 ring-2 ring-amber-500' : 'bg-card']">
                    <p :class="['text-xs font-bold uppercase tracking-wider', filtreStatut === 'brouillon' ? 'text-white/70' : 'text-text-muted']">Brouillons</p>
                    <p :class="['mt-2 font-display text-2xl font-extrabold', filtreStatut === 'brouillon' ? 'text-white' : 'text-amber-600']">
                        {{ kpis.brouillon ?? 0 }}
                    </p>
                </button>
                <button type="button" @click="filtreStatut = 'archive'; filtrer()"
                    :class="['rounded-xl p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'archive' ? 'bg-indigo-600 ring-2 ring-indigo-600' : 'bg-card']">
                    <p :class="['text-xs font-bold uppercase tracking-wider', filtreStatut === 'archive' ? 'text-white/70' : 'text-text-muted']">Archivés</p>
                    <p :class="['mt-2 font-display text-2xl font-extrabold', filtreStatut === 'archive' ? 'text-white' : 'text-indigo-600']">
                        {{ kpis.archive ?? 0 }}
                    </p>
                </button>
            </div>

            <!-- Filtres -->
            <div class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-border-soft bg-white p-3 shadow-sm">
                <select v-model="filtreTypeId" @change="filtrer"
                    class="rounded-lg border border-border-soft px-3 py-2 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                    <option value="">Tous les types</option>
                    <option v-for="t in typesDisponibles" :key="t.code" :value="t.code">{{ t.nom }}</option>
                </select>
                <select v-model="filtreStatut" @change="filtrer"
                    class="rounded-lg border border-border-soft px-3 py-2 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                    <option value="">Tous les statuts</option>
                    <option v-for="s in statuts" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <input v-model="recherche" @keyup.enter="filtrer" type="search" placeholder="Rechercher un événement..."
                    class="flex-1 min-w-40 rounded-lg border border-border-soft px-3 py-2 text-xs outline-none focus:border-moov-blue" />
                <button v-if="filtreTypeId || filtreStatut || recherche" @click="filtreTypeId=''; filtreStatut=''; recherche=''; filtrer()"
                    class="rounded-lg border border-border-soft bg-white px-3 py-2 text-xs font-bold text-text-sub transition hover:bg-page-bg">
                    Réinitialiser
                </button>
            </div>

            <!-- Tableau groupé par type -->
            <div class="space-y-6">
                <template v-for="(groupe, typeCode) in evenementsParType" :key="typeCode">
                    <div v-if="groupe.events.length" class="overflow-hidden rounded-2xl border border-border-soft bg-white shadow-sm">
                        <!-- En-tête du groupe -->
                        <div class="flex items-center justify-between border-b border-border-soft bg-slate-50 px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span :class="['rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider',
                                    couleurType(typeCode).bg, couleurType(typeCode).text]">
                                    {{ couleurType(typeCode).label }}
                                </span>
                                <span class="text-xs text-text-muted font-medium">
                                    {{ groupe.events.length }} événement{{ groupe.events.length > 1 ? 's' : '' }}
                                </span>
                            </div>
                            <div class="flex gap-3 text-xs text-text-muted">
                                <span>{{ groupe.events.filter(e => e.statut === 'publie').length }} publiés</span>
                                <span v-if="groupe.events.filter(e => e.statut === 'en_validation').length > 0"
                                    class="font-bold text-amber-600">
                                    {{ groupe.events.filter(e => e.statut === 'en_validation').length }} à valider
                                </span>
                            </div>
                        </div>

                        <!-- Lignes d'événements -->
                        <div class="divide-y divide-border-soft">
                            <div v-for="ev in groupe.events" :key="ev.id"
                                class="flex flex-wrap items-center gap-3 px-5 py-4 transition hover:bg-slate-50/60">

                                <!-- Titre & réf -->
                                <div class="min-w-0 flex-1">
                                    <Link :href="`/evenements/${ev.id}`"
                                        class="font-semibold text-text-main hover:text-moov-blue transition truncate block">
                                        {{ ev.titre }}
                                    </Link>
                                    <p class="mt-0.5 text-xs text-text-muted">
                                        EV-{{ String(ev.id).padStart(4, '0') }}
                                        <span v-if="ev.lieu?.nom"> · {{ ev.lieu.nom }}</span>
                                    </p>
                                </div>

                                <!-- Date -->
                                <div class="hidden w-32 shrink-0 text-sm text-text-sub sm:block">
                                    {{ formaterDate(ev.date_debut) }}
                                </div>

                                <!-- Statut -->
                                <div class="shrink-0">
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                        couleurStatut(ev.statut).bg, couleurStatut(ev.statut).text]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(ev.statut).dot]" />
                                        {{ couleurStatut(ev.statut).label }}
                                    </span>
                                </div>

                                <!-- Inscrits -->
                                <div class="hidden shrink-0 w-16 text-center sm:block">
                                    <span class="text-sm font-bold text-text-main">{{ ev.inscriptions_count ?? 0 }}</span>
                                    <p class="text-[10px] text-text-muted">inscrits</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex shrink-0 items-center gap-1">
                                    <Link :href="`/evenements/${ev.id}`"
                                        class="rounded-lg p-2 text-text-sub transition hover:bg-blue-50 hover:text-moov-blue"
                                        title="Voir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </Link>
                                    <Link v-if="peutModifierEvent(ev)" :href="`/evenements/${ev.id}/edit`"
                                        class="rounded-lg p-2 text-text-sub transition hover:bg-amber-50 hover:text-amber-600"
                                        title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                    <button v-if="peutPublierEvent(ev)" @click="publierEvenement(ev.id)"
                                        class="rounded-lg p-2 text-text-sub transition hover:bg-emerald-50 hover:text-emerald-600"
                                        title="Valider et publier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button v-if="peutSupprimerEvent(ev)" @click="supprimerEvenement(ev.id)"
                                        class="rounded-lg p-2 text-text-sub transition hover:bg-red-50 hover:text-red-600"
                                        title="Archiver">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="!evenementsAffiches.length" class="rounded-2xl border-2 border-dashed border-border-soft bg-white py-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-3 font-bold text-text-main">Aucun événement trouvé</p>
                    <p class="mt-1 text-sm text-text-sub">
                        {{ filtreTypeId || filtreStatut || recherche ? 'Aucun résultat avec ces filtres.' : (estAdminPur ? 'Aucun événement à superviser.' : 'Créez votre premier événement.') }}
                    </p>
                    <Link v-if="!estAdminPur" href="/evenements/create"
                        class="mt-4 inline-block rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                        Nouvel Événement
                    </Link>
                </div>
            </div>

            <div v-if="evenements?.links?.length > 3" class="mt-6 flex justify-center gap-1">                <template v-for="link in evenements.links" :key="link.label">
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

        
<section class="relative h-[580px] overflow-hidden"
         @mouseenter="heroPause = true"
         @mouseleave="heroPause = false">

    <!-- Images en fond -->
    <div v-for="(img, i) in heroImages" :key="i"
         :class="['absolute inset-0 transition-opacity duration-1000',
                  i === heroIndex ? 'opacity-100 z-10' : 'opacity-0 z-0']">

        <!-- Image avec Ken Burns -->
        <div :class="['absolute inset-0 bg-cover bg-center',
                      i === heroIndex ? 'animate-ken-burns' : '']"
             :style="`background-image: url('${img.url}')`"/>

        <!-- Overlay dégradé sombre -->
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900/30 via-slate-900/50 to-slate-900/80"/>
    </div>

    <!-- Contenu centré -->
    <div class="relative z-20 flex h-full flex-col items-center justify-center px-4 text-center">

        <!-- Badge -->
        <div class="mb-6 animate-fade-up">
            <span class="inline-flex items-center gap-2 rounded-full border border-moov-orange/40 bg-moov-orange/20 px-5 py-2 text-xs font-bold uppercase tracking-[0.25em] text-white backdrop-blur-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-moov-orange animate-pulse"/>
                Moov Africa Burkina · dCIRP
            </span>
        </div>

        <!-- Titre -->
        <h1 class="max-w-4xl font-display text-4xl font-extrabold leading-tight text-white sm:text-5xl lg:text-6xl animate-fade-up">
            Les événements qui font<br>
            <span class="text-moov-orange">bouger le Burkina</span>
        </h1>

        <!-- Caption dynamique (change avec chaque image) -->
        <p :key="heroIndex"
           class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-white/80 animate-fade-up">
            {{ heroImages[heroIndex].caption }}
        </p>

        <!-- KPIs -->
        <div class="mt-10 flex gap-6 animate-fade-up">
            <div class="text-center">
                <p class="font-display text-3xl font-extrabold text-white">{{ kpis.total ?? 0 }}</p>
                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-white/60">
                    Événement{{ (kpis.total ?? 0) > 1 ? 's' : '' }}
                </p>
            </div>
            <div class="h-12 w-px bg-white/20 self-center"/>
            <div class="text-center">
                <p class="font-display text-3xl font-extrabold text-white">{{ kpis.en_cours ?? 0 }}</p>
                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-white/60">En cours</p>
            </div>
            <div class="h-12 w-px bg-white/20 self-center"/>
            <div class="text-center">
                <p class="font-display text-3xl font-extrabold text-moov-orange">7</p>
                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-white/60">Typologies</p>
            </div>
        </div>

        <!-- Points de navigation -->
        <div class="mt-10 flex items-center gap-2">
            <button v-for="(_, i) in heroImages" :key="i"
                    @click="allerHeroImage(i)"
                    :class="['rounded-full transition-all duration-300',
                             i === heroIndex
                                 ? 'h-2 w-8 bg-moov-orange'
                                 : 'h-2 w-2 bg-white/40 hover:bg-white/60']"/>
        </div>
    </div>

    <!-- Flèche gauche -->
    <button @click="heroPrecedent"
            class="absolute left-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/20 p-3 text-white backdrop-blur-sm transition hover:bg-black/40">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    <!-- Flèche droite -->
    <button @click="heroSuivant"
            class="absolute right-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/20 p-3 text-white backdrop-blur-sm transition hover:bg-black/40">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    <!-- Barre de progression en bas -->
    <div class="absolute bottom-0 left-0 z-20 h-1 w-full bg-white/10">
        <div class="h-full bg-moov-orange transition-all duration-300"
             :style="`width: ${((heroIndex + 1) / heroImages.length) * 100}%`"/>
    </div>

</section>
       
            <section class="sticky top-16 z-30 border-y border-slate-200 bg-white/95 backdrop-blur">
                <div class="mx-auto max-w-7xl px-4">
                    <div class="flex gap-2 overflow-x-auto py-4" style="scrollbar-width:none">
                        <button @click="filtreTypeId = ''; filtrer()"
                            :class="['flex-shrink-0 whitespace-nowrap rounded-full border-2 px-5 py-2 text-sm font-bold transition',
                                !filtreTypeId
                                    ? 'border-moov-blue bg-moov-blue text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-moov-blue/50']">
                            Tous
                        </button>
                        <button v-for="t in typesDisponibles" :key="t.code"@click="filtreTypeId = t.code; filtrer()"
                            :class="['flex-shrink-0 whitespace-nowrap rounded-full border-2 px-5 py-2 text-sm font-bold transition',
                               filtreTypeId == t.code
                                    ? 'border-moov-blue bg-moov-blue text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-moov-blue/50']">
                            {{ t.nom }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

                <p class="mb-6 text-sm text-slate-600">
                    <span class="font-bold text-slate-900">{{ evenementsAffiches.length }}</span>
                    événement{{ evenementsAffiches.length > 1 ? 's' : '' }} trouvé{{ evenementsAffiches.length > 1 ? 's' : '' }}
                </p>

                <div v-if="evenementsAffiches.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                    <article v-for="ev in evenementsAffiches" :key="ev.id"
                        class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">

                        <!-- ─── VISUEL (image ou gradient) ─── -->
                        <div class="relative h-48 overflow-hidden">

                            <img v-if="ev.visuel_url" :src="ev.visuel_url" :alt="ev.titre"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"/>

                            <!-- Gradient élégant si pas d'image -->
                            <div v-else
                                 :class="['flex h-full w-full items-center justify-center bg-gradient-to-br',
                                    {
                                        BARA_MOUSSO: 'from-rose-400 to-pink-600',
                                        CONF: 'from-blue-400 to-indigo-600',
                                        SPORT: 'from-cyan-400 to-blue-600',
                                        CHALLENGE: 'from-violet-400 to-purple-600',
                                        FORMATION: 'from-emerald-400 to-teal-600',
                                        HACK: 'from-orange-400 to-red-600',
                                        SALON: 'from-indigo-400 to-violet-600',
                                    }[ev.type_evenement?.code] || 'from-slate-400 to-slate-600']">
                                <p class="font-display text-7xl font-extrabold text-white/30">
                                    {{ ev.type_evenement?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                                </p>
                            </div>

                            <!-- Date overlay (gauche haut) -->
                            <div class="absolute left-3 top-3 rounded-lg bg-white/95 px-3 py-2 text-center shadow-md backdrop-blur">
                                <p class="font-display text-xl font-extrabold leading-none text-slate-900">
                                    {{ formaterMoisJour(ev.date_debut).jour }}
                                </p>
                                <p class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-600">
                                    {{ formaterMoisJour(ev.date_debut).mois }}
                                </p>
                            </div>

                            <!-- Badge type (droite haut) -->
                            <span :class="['absolute right-3 top-3 rounded-md px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider shadow-sm',
                                couleurType(ev.type_evenement?.code).bg,
                                couleurType(ev.type_evenement?.code).text]">
                                {{ ev.type_evenement?.nom }}
                            </span>

                            <!-- Badge statut (bas) -->
                            <span :class="['absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold shadow-sm',
                                couleurStatutDyn(statutDynamique(ev)).bg,
                                couleurStatutDyn(statutDynamique(ev)).text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', couleurStatutDyn(statutDynamique(ev)).dot]" />
                                {{ couleurStatutDyn(statutDynamique(ev)).label }}
                            </span>
                        </div>

                        <!-- ─── BODY ─── -->
                        <div class="flex flex-1 flex-col p-5">

                            <h3 class="line-clamp-2 font-display text-base font-extrabold leading-snug text-slate-900 group-hover:text-moov-blue">
                                <Link :href="`/evenements/${ev.id}`">{{ ev.titre }}</Link>
                            </h3>

                            <!-- Méta-infos -->
                            <div class="mt-3 flex-1 space-y-1.5 text-sm text-slate-600">
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ formaterDate(ev.date_debut) }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="truncate">{{ ev.lieu?.nom ?? 'Lieu à définir' }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span :class="formaterPrix(ev) === 'Gratuit' ? 'font-bold text-emerald-700' : 'font-bold text-slate-900'">
                                        {{ formaterPrix(ev) }}
                                    </span>
                                </p>
                            </div>

                            <!-- ─── ACTIONS (bouton contextuel) ─── -->
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <template v-if="actionEvenement(ev).style === 'primary'">
                                    <Link :href="actionEvenement(ev).href"
                                          class="block w-full rounded-lg bg-moov-blue px-4 py-2.5 text-center text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                                        {{ actionEvenement(ev).label }}
                                    </Link>
                                </template>
                                <template v-else-if="actionEvenement(ev).style === 'secondary'">
                                    <Link :href="actionEvenement(ev).href"
                                          class="block w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-700 transition hover:border-moov-blue hover:text-moov-blue">
                                        {{ actionEvenement(ev).label }}
                                    </Link>
                                </template>
                                <template v-else>
                                    <button disabled
                                            class="block w-full cursor-not-allowed rounded-lg bg-slate-100 px-4 py-2.5 text-center text-sm font-bold text-slate-500">
                                        {{ actionEvenement(ev).label }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Vide -->
                <div v-else class="rounded-xl border-2 border-dashed border-slate-200 bg-white py-20 text-center">
                    <svg class="mx-auto h-16 w-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <h3 class="mt-4 font-display text-lg font-bold text-slate-900">Aucun événement disponible</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ filtreTypeId ? "Aucun événement dans cette catégorie pour le moment." : "Aucun événement publié pour le moment." }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="evenements?.links?.length > 3" class="mt-12 flex justify-center gap-1.5">
                    <template v-for="link in evenements.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                              :class="['rounded-lg border-2 px-4 py-2 text-sm font-bold transition',
                                  link.active
                                      ? 'border-moov-blue bg-moov-blue text-white'
                                      : 'border-slate-200 bg-white text-slate-700 hover:border-moov-blue']" />
                        <span v-else v-html="link.label"
                              class="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm text-slate-400" />
                    </template>
                </div>
            </section>
        </template>
<!-- Modal de confirmation -->
        <ConfirmModal
            :show="modalConfirm.show"
            :type="modalConfirm.type"
            :title="modalConfirm.title"
            :message="modalConfirm.message"
            :confirm-text="modalConfirm.confirmText"
            @confirm="executerAction"
            @cancel="fermerModal"
        />
    </component>
</template>