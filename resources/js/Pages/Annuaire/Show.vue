<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    participant:  Object,
    inscriptions: Array,
})

const initiales = computed(() =>
    `${props.participant.prenom?.[0] ?? ''}${props.participant.nom?.[0] ?? ''}`.toUpperCase()
)

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const couleurStatut = (statut) => ({
    en_attente:  { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-500' },
    en_analyse:  { bg: 'bg-blue-50',     text: 'text-blue-700',     dot: 'bg-blue-500' },
    acceptee:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  dot: 'bg-emerald-500' },
    confirmee:   { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600' },
    refusee:     { bg: 'bg-red-50',      text: 'text-red-700',      dot: 'bg-red-500' },
    annulee:     { bg: 'bg-slate-100',   text: 'text-slate-600',    dot: 'bg-slate-400' },
    present:     { bg: 'bg-emerald-200', text: 'text-emerald-900',  dot: 'bg-emerald-700' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400' })

const labelStatut = (statut) => ({
    en_attente:  'En attente',
    en_analyse:  'En analyse',
    acceptee:    'Acceptée',
    confirmee:   'Confirmée',
    refusee:     'Refusée',
    annulee:     'Annulée',
    present:     'Présent',
}[statut] || statut)

const couleurType = (code) => ({
    BARA_MOUSSO: 'bg-amber-50 text-amber-700',
    CONF:        'bg-rose-50 text-rose-700',
    SPORT:       'bg-blue-50 text-blue-700',
    CHALLENGE:   'bg-violet-50 text-violet-700',
    FORMATION:   'bg-emerald-50 text-emerald-700',
    HACK:        'bg-orange-50 text-orange-700',
    SALON:       'bg-indigo-50 text-indigo-700',
}[code] || 'bg-slate-50 text-slate-700')

// Stats du participant
const totalInscriptions = computed(() => props.inscriptions.length)
const totalConfirmees = computed(() =>
    props.inscriptions.filter(i => ['confirmee', 'present'].includes(i.statut)).length
)
const totalPresents = computed(() =>
    props.inscriptions.filter(i => i.statut === 'present').length
)
const totalRefusees = computed(() =>
    props.inscriptions.filter(i => i.statut === 'refusee').length
)
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6">
            <Link href="/annuaire"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-text-sub hover:text-moov-blue">
                ← Retour à l'annuaire
            </Link>
        </div>

        <!-- ── PROFIL HEADER ── -->
        <div class="mb-6 rounded-xl bg-card p-6 shadow-card">
            <div class="flex flex-wrap items-start gap-4">
                <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-2xl font-bold text-white">
                    {{ initiales }}
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-3xl font-extrabold text-text-main">
                        {{ participant.prenom }} {{ participant.nom }}
                    </h1>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <a :href="`mailto:${participant.email}`"
                           class="flex items-center gap-2 text-text-sub hover:text-moov-blue">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ participant.email }}
                        </a>
                        <a v-if="participant.telephone"
                           :href="`tel:${participant.telephone}`"
                           class="flex items-center gap-2 text-text-sub hover:text-moov-blue">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ participant.telephone }}
                        </a>
                    </div>
                    <p class="mt-2 text-xs text-text-muted">
                        Membre depuis le {{ formaterDate(participant.created_at) }}
                    </p>
                </div>

                <span v-if="!participant.is_active"
                      class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-slate-600">
                    Compte désactivé
                </span>
                <span v-else
                      class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"/>
                    Compte actif
                </span>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Inscriptions</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                    {{ totalInscriptions }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Confirmées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">
                    {{ totalConfirmees }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Présent</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-700">
                    {{ totalPresents }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Refusées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-red-600">
                    {{ totalRefusees }}
                </p>
            </div>
        </div>

        
        <div class="rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft p-5">
                <h2 class="font-display text-base font-bold text-text-main">
                    Historique des inscriptions
                </h2>
            </div>

            <div v-if="inscriptions.length" class="divide-y divide-border-soft">
                <div v-for="i in inscriptions" :key="i.id"
                     class="flex flex-wrap items-start justify-between gap-4 p-5 transition hover:bg-page-bg/50">

                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <span :class="['mt-1 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                            couleurType(i.evenement?.type?.code)]">
                            {{ i.evenement?.type?.nom ?? '—' }}
                        </span>

                        <div class="min-w-0">
                            <Link :href="`/evenements/${i.evenement?.id}`"
                                  class="font-display text-base font-bold text-text-main hover:text-moov-blue">
                                {{ i.evenement?.titre }}
                            </Link>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                                <span>{{ formaterDate(i.evenement?.date_debut) }}</span>
                                <span v-if="i.evenement?.lieu">·</span>
                                <span v-if="i.evenement?.lieu">{{ i.evenement.lieu.nom }}</span>
                                <span v-if="i.tarif">·</span>
                                <span v-if="i.tarif">
                                    {{ i.tarif.libelle }}
                                    <template v-if="i.tarif.montant > 0">
                                        ({{ Number(i.tarif.montant).toLocaleString('fr-FR') }} {{ i.tarif.devise }})
                                    </template>
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-text-muted">
                                Référence : <span class="font-mono font-bold">{{ i.qr_code }}</span>
                                · Soumis le {{ formaterDate(i.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold',
                            couleurStatut(i.statut).bg, couleurStatut(i.statut).text]">
                            <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(i.statut).dot]"/>
                            {{ labelStatut(i.statut) }}
                        </span>

                        <Link :href="`/inscriptions/${i.id}`"
                              class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                            Voir dossier
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucune inscription pour ce participant</p>
                <p class="mt-1 text-sm text-text-sub">
                    Cet utilisateur n'a pas encore soumis de dossier de candidature.
                </p>
            </div>
        </div>

    </DashboardLayout>
</template>