<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:  { type: Object, required: true },
    campagnes:  { type: Array, default: () => [] },
    kpis:       { type: Object, default: () => ({}) },
})

// ─── HELPERS ────────────────────────
const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const formatDateTime = (d) => d
    ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
    : '—'

const labelCible = (mode) => ({
    tous: 'Tous les inscrits',
    valides: 'Validés',
    presents: 'Présents',
    absents: 'Absents',
    refuses: 'Refusés',
}[mode] || mode)

const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    envoyee:   { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Envoyée' },
    erreur:    { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Erreur' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statut })

// ─── ACTIONS ────────────────────────
const envoyerBrouillon = (campagne) => {
    if (!confirm(`Envoyer la campagne "${campagne.objet}" maintenant ?\nCette action enverra ${campagne.nb_destinataires || '?'} email(s).`)) return
    router.post(`/evenements/${props.evenement.id}/communication/campaigns/${campagne.id}/send`, {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

       
        <Link :href="`/evenements/${evenement.id}`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
             Retour à l'événement
        </Link>

        
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Communication · Campagnes email
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                     Campagnes Email
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                </p>
            </div>

            <Link :href="`/evenements/${evenement.id}/communication/campaigns/create`"
                  class="inline-flex items-center gap-2 rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle campagne
            </Link>
        </div>

       
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total campagnes</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ kpis.total_campagnes ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Envoyées</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ kpis.campagnes_envoyees ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Brouillons</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-amber-600">{{ kpis.campagnes_brouillon ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Emails envoyés</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-orange">{{ kpis.total_emails_envoyes ?? 0 }}</p>
            </div>
        </div>

       
        <div v-if="campagnes.length === 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Aucune campagne</h3>
            <p class="mt-1 text-sm text-text-sub">
                Créez votre première campagne pour communiquer avec les participants.
            </p>
            <Link :href="`/evenements/${evenement.id}/communication/campaigns/create`"
                  class="mt-4 inline-block rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-blue-700">
                Créer ma première campagne
            </Link>
        </div>

        <div v-else class="space-y-3">

            <div v-for="campagne in campagnes" :key="campagne.id"
                 class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-lg">

                <div class="flex flex-wrap items-start justify-between gap-4">

                    <!-- Infos -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                couleurStatut(campagne.statut).bg, couleurStatut(campagne.statut).text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(campagne.statut).dot]"/>
                                {{ couleurStatut(campagne.statut).label }}
                            </span>
                            <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                 {{ labelCible(campagne.mode_destinataires) }}
                            </span>
                        </div>

                        <Link :href="`/evenements/${evenement.id}/communication/campaigns/${campagne.id}`"
                              class="mt-2 block">
                            <h3 class="font-display text-base font-extrabold text-text-main hover:text-moov-blue">
                                {{ campagne.objet }}
                            </h3>
                        </Link>

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                            <span v-if="campagne.statut === 'envoyee'">
                                 <strong class="text-emerald-600">{{ campagne.nb_envoyes }}</strong> envoyés
                            </span>
                            <span v-if="campagne.nb_erreurs > 0">
                                 <strong class="text-red-600">{{ campagne.nb_erreurs }}</strong> erreurs
                            </span>
                            <span v-if="campagne.date_envoi">
                                · Envoyée le {{ formatDateTime(campagne.date_envoi) }}
                            </span>
                            <span v-else>
                                Créée le {{ formatDate(campagne.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="`/evenements/${evenement.id}/communication/campaigns/${campagne.id}`"
                              class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-sub transition hover:border-moov-blue hover:text-moov-blue">
                            Voir détails
                        </Link>

                        <button v-if="campagne.statut === 'brouillon'"
                                @click="envoyerBrouillon(campagne)"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-emerald-700">
                             Envoyer maintenant
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>