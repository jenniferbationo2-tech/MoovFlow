<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    inscription: { type: Object, required: true },
})

const evenement = computed(() => props.inscription.evenement)
const typeCode = computed(() => evenement.value?.type_evenement?.code)

// Étapes du workflow
const etapes = computed(() => {
    const ne = !['CONF', 'FORMATION'].includes(typeCode.value)
    if (!ne) {
        return [
            { num: 1, titre: 'Pré-inscription', statuts: ['preinscrit'] },
            { num: 2, titre: 'Validation',      statuts: ['en_analyse', 'recommandee'] },
            { num: 3, titre: 'Confirmation',    statuts: ['acceptee', 'confirmee'] },
            { num: 4, titre: 'Présence',        statuts: ['present'] },
        ]
    }
    return [
        { num: 1, titre: 'Pré-inscription', statuts: ['preinscrit'] },
        { num: 2, titre: 'Présélection',    statuts: ['preselectionne'] },
        { num: 3, titre: 'Dossier complet', statuts: ['dossier_soumis', 'en_analyse'] },
        { num: 4, titre: 'Recommandation',  statuts: ['recommandee'] },
        { num: 5, titre: 'Validation',      statuts: ['acceptee', 'confirmee'] },
    ]
})

const etapeActuelle = computed(() => {
    const statut = props.inscription.statut
    if (['refusee', 'annulee'].includes(statut)) return -1
    if (statut === 'present') return etapes.value.length

    for (let i = 0; i < etapes.value.length; i++) {
        if (etapes.value[i].statuts.includes(statut)) return i + 1
    }
    return 0
})

const couleurStatut = computed(() => ({
    preinscrit:     'bg-amber-100 text-amber-700',
    preselectionne: 'bg-blue-100 text-blue-700',
    dossier_soumis: 'bg-indigo-100 text-indigo-700',
    en_analyse:     'bg-violet-100 text-violet-700',
    recommandee:    'bg-cyan-100 text-cyan-700',
    acceptee:       'bg-emerald-100 text-emerald-700',
    confirmee:      'bg-emerald-200 text-emerald-800',
    refusee:        'bg-red-100 text-red-700',
    present:        'bg-emerald-200 text-emerald-900',
    absent:         'bg-slate-100 text-slate-600',
    annulee:        'bg-slate-100 text-slate-600',
}[props.inscription.statut] || 'bg-slate-100 text-slate-600'))

const labelStatut = computed(() => ({
    preinscrit:     'Pré-inscrit',
    preselectionne: 'Présélectionné',
    dossier_soumis: 'Dossier soumis',
    en_analyse:     'En analyse',
    recommandee:    'Recommandée',
    acceptee:       'Acceptée',
    confirmee:      'Confirmée',
    refusee:        'Refusée',
    present:        'Présent',
    absent:         'Absent',
    annulee:        'Annulée',
}[props.inscription.statut] || props.inscription.statut))

// Lien dossier complet
const lienDossierComplet = computed(() => {
    if (props.inscription.statut !== 'preselectionne') return null
    return `/inscriptions/${props.inscription.id}/dossier-complet`
})

const annuler = () => {
    if (confirm('Annuler définitivement cette inscription ?')) {
        router.post(`/inscriptions/${props.inscription.id}/annuler`)
    }
}

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <PublicLayout>
        <section class="bg-page-bg py-10">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

                <Link href="/mes-inscriptions"
                      class="mb-6 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Mes inscriptions
                </Link>

                <!-- Carte principale -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-card">

                    <!-- En-tête événement -->
                    <div class="border-b border-border-soft p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex-1">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    {{ evenement.type_evenement?.nom }}
                                </p>
                                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main">
                                    {{ evenement.titre }}
                                </h1>
                                <p class="mt-2 text-sm text-text-sub">
                                     {{ formaterDate(evenement.date_debut) }}
                                    <span v-if="evenement.lieu" class="ml-3">📍 {{ evenement.lieu.nom }}</span>
                                </p>
                            </div>
                            <span :class="['rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider', couleurStatut]">
                                {{ labelStatut }}
                            </span>
                        </div>
                    </div>

                    <!-- WORKFLOW VISUEL -->
                    <div v-if="!['refusee', 'annulee'].includes(inscription.statut)" class="p-6">
                        <h2 class="mb-5 font-display text-sm font-extrabold uppercase tracking-wider text-text-muted">
                            Progression de votre inscription
                        </h2>

                        <div class="flex items-center justify-between gap-2">
                            <template v-for="(et, i) in etapes" :key="et.num">
                                <div class="flex flex-1 flex-col items-center gap-2">
                                    <div :class="['flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-full font-display text-sm font-extrabold transition',
                                        et.num < etapeActuelle ? 'bg-emerald-500 text-white' :
                                        et.num === etapeActuelle ? 'bg-moov-blue text-white shadow-lg ring-4 ring-moov-blue/20' :
                                        'bg-page-bg text-text-muted']">
                                        <svg v-if="et.num < etapeActuelle" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span v-else>{{ et.num }}</span>
                                    </div>
                                    <p :class="['text-center text-[10px] sm:text-xs font-bold',
                                        et.num === etapeActuelle ? 'text-moov-blue' :
                                        et.num < etapeActuelle ? 'text-emerald-600' :
                                        'text-text-muted']">
                                        {{ et.titre }}
                                    </p>
                                </div>
                                <div v-if="i < etapes.length - 1"
                                     :class="['h-0.5 flex-1 transition',
                                         et.num < etapeActuelle ? 'bg-emerald-500' : 'bg-border-soft']"/>
                            </template>
                        </div>
                    </div>

                    <!-- Action requise : COMPLÉTER LE DOSSIER -->
                    <div v-if="inscription.statut === 'preselectionne'"
                         class="border-y border-amber-200 bg-amber-50 p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <svg class="h-6 w-6 flex-shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <div>
                                    <p class="font-display text-base font-extrabold text-amber-900">
                                        Vous êtes présélectionné(e) !
                                    </p>
                                    <p class="mt-1 text-sm text-amber-800">
                                        Complétez votre dossier complet pour finaliser votre candidature.
                                    </p>
                                </div>
                            </div>
                            <Link :href="lienDossierComplet"
                                  class="rounded-lg bg-moov-orange px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-orange-700">
                                Compléter mon dossier →
                            </Link>
                        </div>
                    </div>

                    <!-- QR CODE si confirmé -->
                    <div v-if="['confirmee', 'acceptee', 'present'].includes(inscription.statut)"
                         class="border-y border-emerald-200 bg-emerald-50 p-6 text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                            Votre badge d'accès
                        </p>
                        <p class="mt-2 font-mono text-3xl font-extrabold text-emerald-900 tracking-wider">
                            {{ inscription.qr_code }}
                        </p>
                        <p class="mt-2 text-sm text-emerald-800">
                            Présentez ce code à l'entrée de l'événement
                        </p>
                    </div>

                    <!-- Si REFUSÉ : motif -->
                    <div v-if="inscription.statut === 'refusee' && inscription.motif_refus"
                         class="border-y border-red-200 bg-red-50 p-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                            Motif du refus
                        </p>
                        <p class="mt-2 text-sm text-red-900">{{ inscription.motif_refus }}</p>
                        <Link href="/evenements"
                              class="mt-3 inline-block text-xs font-bold text-moov-blue hover:underline">
                            Découvrir d'autres événements →
                        </Link>
                    </div>

                    <!-- Détails -->
                    <div class="space-y-4 p-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Date de soumission
                            </p>
                            <p class="mt-1 text-sm text-text-main">{{ formaterDate(inscription.created_at) }}</p>
                        </div>

                        <div v-if="inscription.motivation">
                            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Votre motivation
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm text-text-main">{{ inscription.motivation }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="['preinscrit', 'preselectionne', 'dossier_soumis'].includes(inscription.statut)"
                         class="border-t border-border-soft bg-page-bg/30 p-6">
                        <button @click="annuler"
                                class="text-sm font-bold text-red-600 transition hover:text-red-700">
                            Annuler mon inscription
                        </button>
                    </div>
                </div>

            </div>
        </section>
    </PublicLayout>
</template>