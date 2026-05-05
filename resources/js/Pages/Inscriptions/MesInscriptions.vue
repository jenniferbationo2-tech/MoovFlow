<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    inscriptions: Object,
    stats:        Object,
    filters:      Object,
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)


const filtreStatut = ref(props.filters?.statut ?? '')

const filtrer = (statut) => {
    filtreStatut.value = statut
    router.get('/mes-inscriptions',
        statut ? { statut } : {},
        { preserveState: true, preserveScroll: true }
    )
}


const couleurStatut = (statut) => ({
    en_attente:  { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-500',    label: 'En attente d\'analyse' },
    en_analyse:  { bg: 'bg-blue-50',     text: 'text-blue-700',     dot: 'bg-blue-500',     label: 'En cours d\'analyse' },
    acceptee:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  dot: 'bg-emerald-500',  label: 'Acceptée — En attente de paiement' },
    confirmee:   { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Confirmée' },
    refusee:     { bg: 'bg-red-50',      text: 'text-red-700',      dot: 'bg-red-500',      label: 'Refusée' },
    annulee:     { bg: 'bg-slate-100',   text: 'text-slate-600',    dot: 'bg-slate-400',    label: 'Annulée' },
    present:     { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Présent à l\'événement' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const couleurType = (code) => ({
    BARA_MOUSSO: { bg: 'bg-amber-50',    text: 'text-amber-700' },
    CONF:        { bg: 'bg-rose-50',     text: 'text-rose-700' },
    SPORT:       { bg: 'bg-blue-50',     text: 'text-blue-700' },
    CHALLENGE:   { bg: 'bg-violet-50',   text: 'text-violet-700' },
    FORMATION:   { bg: 'bg-emerald-50',  text: 'text-emerald-700' },
    HACK:        { bg: 'bg-orange-50',   text: 'text-orange-700' },
    SALON:       { bg: 'bg-indigo-50',   text: 'text-indigo-700' },
}[code] || { bg: 'bg-slate-50', text: 'text-slate-700' })

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


const annuler = (inscription) => {
    if (confirm(`Annuler votre dossier pour "${inscription.evenement?.titre}" ?`)) {
        router.post(`/inscriptions/${inscription.id}/annuler`)
    }
}
</script>

<template>
    <PublicLayout>
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">

            <!-- ── EN-TÊTE ── -->
            <div class="mb-8">
                <h1 class="font-display text-3xl font-extrabold text-text-main">
                    Mes inscriptions
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Suivez l'état de vos dossiers de candidature aux événements
                </p>
            </div>

           
            <div class="mb-8 grid grid-cols-2 gap-3 md:grid-cols-4">
                <button @click="filtrer('')"
                        :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                            !filtreStatut ? 'ring-2 ring-moov-blue' : '']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">{{ stats.total }}</p>
                </button>
                <button @click="filtrer('en_attente')"
                        :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                            filtreStatut === 'en_attente' ? 'ring-2 ring-amber-500' : '']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">En attente</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-amber-600">{{ stats.en_attente }}</p>
                </button>
                <button @click="filtrer('confirmee')"
                        :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                            filtreStatut === 'confirmee' ? 'ring-2 ring-emerald-500' : '']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Acceptées</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.acceptees }}</p>
                </button>
                <button @click="filtrer('refusee')"
                        :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                            filtreStatut === 'refusee' ? 'ring-2 ring-red-500' : '']">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Refusées</p>
                    <p class="mt-2 font-display text-2xl font-extrabold text-red-600">{{ stats.refusees }}</p>
                </button>
            </div>

           
            <div v-if="inscriptions.data?.length" class="space-y-4">

                <article v-for="insc in inscriptions.data" :key="insc.id"
                         class="overflow-hidden rounded-xl bg-card shadow-card transition hover:shadow-card-hover">

                    <div class="grid grid-cols-1 md:grid-cols-[180px,1fr]">

                        <!-- Visuel + Date -->
                        <div class="relative h-32 md:h-auto">
                            <img v-if="insc.evenement?.visuel_url"
                                 :src="insc.evenement.visuel_url"
                                 :alt="insc.evenement.titre"
                                 class="h-full w-full object-cover"/>

                            <!-- Placeholder sobre -->
                            <div v-else
                                 :class="['flex h-full w-full items-center justify-center', couleurType(insc.evenement?.type?.code).bg]">
                                <p :class="['font-display text-3xl font-extrabold opacity-30', couleurType(insc.evenement?.type?.code).text]">
                                    {{ insc.evenement?.type?.nom?.substring(0, 2).toUpperCase() ?? 'EV' }}
                                </p>
                            </div>

                            <div class="absolute left-3 top-3 rounded-md bg-white px-2 py-1 text-center shadow-md">
                                <p class="font-display text-base font-extrabold leading-none text-text-main">
                                    {{ formaterMoisJour(insc.evenement?.date_debut).jour }}
                                </p>
                                <p class="text-[9px] font-bold uppercase tracking-wider text-text-sub">
                                    {{ formaterMoisJour(insc.evenement?.date_debut).mois }}
                                </p>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="flex flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span :class="['mb-2 inline-block rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                        couleurType(insc.evenement?.type?.code).bg,
                                        couleurType(insc.evenement?.type?.code).text]">
                                        {{ insc.evenement?.type?.nom }}
                                    </span>

                                    <h3 class="font-display text-lg font-extrabold leading-tight text-text-main">
                                        {{ insc.evenement?.titre }}
                                    </h3>

                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                                        <span>{{ formaterDate(insc.evenement?.date_debut) }}</span>
                                        <span v-if="insc.evenement?.lieu">·</span>
                                        <span v-if="insc.evenement?.lieu">{{ insc.evenement.lieu.nom }}</span>
                                    </div>

                                    <p class="mt-2 text-xs text-text-muted">
                                        Référence dossier :
                                        <span class="font-mono font-bold text-text-main">{{ insc.qr_code }}</span>
                                    </p>
                                </div>

                                <!-- Statut -->
                                <span :class="['inline-flex flex-shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
                                    couleurStatut(insc.statut).bg, couleurStatut(insc.statut).text]">
                                    <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(insc.statut).dot]"/>
                                    {{ couleurStatut(insc.statut).label }}
                                </span>
                            </div>

                            <!-- Motif refus si refusée -->
                            <div v-if="insc.statut === 'refusee' && insc.motif_refus"
                                 class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-red-700">Motif du refus</p>
                                <p class="mt-1 text-sm text-red-900">{{ insc.motif_refus }}</p>
                            </div>

                        
                            <div v-if="insc.statut === 'acceptee' && insc.tarif?.montant > 0"
                                 class="mt-3 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Paiement requis</p>
                                    <p class="mt-1 font-display text-lg font-extrabold text-amber-900">
                                        {{ Number(insc.tarif.montant).toLocaleString('fr-FR') }} {{ insc.tarif.devise }}
                                    </p>
                                </div>
                                <Link :href="`/inscriptions/${insc.id}/payer`"
                                      class="rounded-lg bg-moov-orange px-4 py-2 text-xs font-bold text-white transition hover:bg-moov-orange-dark">
                                    Payer maintenant →
                                </Link>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-border-soft pt-4">
                                <div class="text-xs text-text-muted">
                                    Soumis le {{ formaterDate(insc.created_at) }}
                                </div>
                                <div class="flex gap-2">
                                    <Link :href="`/evenements/${insc.evenement?.id}`"
                                          class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-bold text-text-sub transition hover:border-moov-blue hover:text-moov-blue">
                                        Voir l'événement
                                    </Link>
                                    <Link :href="`/inscriptions/${insc.id}`"
                                          class="rounded-lg bg-moov-blue px-3 py-1.5 text-xs font-bold text-white transition hover:bg-moov-blue-dark">
                                        Détails du dossier
                                    </Link>
                                    <button v-if="['en_attente', 'en_analyse', 'acceptee'].includes(insc.statut)"
                                            @click="annuler(insc)"
                                            class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-600 transition hover:border-red-400 hover:bg-red-50">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

            </div>

           
            <div v-else class="rounded-xl border border-dashed border-border-soft bg-white py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>

                <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">
                    {{ filtreStatut ? 'Aucun dossier dans cette catégorie' : 'Aucun dossier d\'inscription' }}
                </h3>
                <p class="mt-2 text-sm text-text-sub">
                    {{ filtreStatut
                        ? 'Essayez un autre filtre ou consultez tous vos dossiers.'
                        : 'Vous n\'avez encore soumis aucun dossier de candidature.' }}
                </p>

                <Link href="/evenements"
                      class="mt-6 inline-block rounded-lg bg-moov-noir px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    Découvrir les événements →
                </Link>
            </div>

            
            <div v-if="inscriptions.links?.length > 3" class="mt-8 flex justify-center gap-1">
                <template v-for="link in inscriptions.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                          :class="['rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                              link.active
                                  ? 'bg-moov-blue text-white'
                                  : 'border border-border-soft bg-white text-text-sub hover:border-moov-blue/30']"/>
                    <span v-else v-html="link.label"
                          class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-sm text-text-muted"/>
                </template>
            </div>

        </div>
    </PublicLayout>
</template>