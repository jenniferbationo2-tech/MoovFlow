<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    evenement: { type: Object, required: true },
})

const form = useForm({
    evenement_id:       props.evenement.id,
    motivation_courte:  '',
    reglement_accepte:  false,
})

const submit = () => {
    form.post('/inscriptions', {
        preserveScroll: true,
    })
}

const typeCode = computed(() => props.evenement.type_evenement?.code)
const necessiteEtape2 = computed(() => !['CONF', 'FORMATION'].includes(typeCode.value))

const couleurType = computed(() => ({
    BARA_MOUSSO: { bg: 'bg-rose-500', txt: 'text-rose-700', label: 'Bara Mousso' },
    CONF:        { bg: 'bg-blue-500', txt: 'text-blue-700', label: 'Conférence' },
    SPORT:       { bg: 'bg-cyan-500', txt: 'text-cyan-700', label: 'Tournoi sportif' },
    CHALLENGE:   { bg: 'bg-violet-500', txt: 'text-violet-700', label: 'Challenge Innovation' },
    FORMATION:   { bg: 'bg-emerald-500', txt: 'text-emerald-700', label: 'Formation' },
    HACK:        { bg: 'bg-orange-500', txt: 'text-orange-700', label: 'Hackathon' },
}[typeCode.value] || { bg: 'bg-slate-500', txt: 'text-slate-700', label: 'Événement' }))

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
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                <!-- Retour -->
                <Link :href="`/evenements/${evenement.id}`"
                      class="mb-6 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour à l'événement
                </Link>

                <!-- Carte d'événement -->
                <div class="mb-6 overflow-hidden rounded-2xl border border-border-soft bg-white shadow-card">
                    <div class="p-6 sm:p-7">
                        <div class="flex items-center gap-2">
                            <span :class="['h-2 w-2 rounded-full', couleurType.bg]"/>
                            <span class="text-xs font-bold uppercase tracking-wider text-text-muted">
                                {{ couleurType.label }}
                            </span>
                        </div>

                        <h1 class="mt-3 font-display text-2xl font-extrabold leading-snug text-text-main sm:text-3xl">
                            Pré-inscription à : {{ evenement.titre }}
                        </h1>

                        <div class="mt-5 flex flex-col gap-3 border-t border-border-soft pt-5 sm:flex-row sm:gap-8">
                            <div class="flex items-center gap-2.5 text-sm">
                                <svg class="h-4 w-4 flex-shrink-0 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-medium text-text-main">{{ formaterDate(evenement.date_debut) }}</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-sm">
                                <svg class="h-4 w-4 flex-shrink-0 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="font-medium text-text-main">{{ evenement.lieu?.nom ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info workflow -->
                <div v-if="necessiteEtape2"
                     class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="font-bold text-blue-900">
                                Inscription en 2 étapes
                            </p>
                            <p class="mt-1 text-blue-800">
                                Cet événement nécessite une présélection.
                                <strong>Étape 1</strong> : pré-inscription rapide (maintenant).
                                <strong>Étape 2</strong> : si vous êtes présélectionné(e), vous recevrez un email pour
                                compléter votre dossier complet.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="font-bold text-emerald-900">Inscription rapide</p>
                            <p class="mt-1 text-emerald-800">
                                Cet événement est ouvert dans la limite des places disponibles.
                                Votre inscription sera validée par le responsable dCIRP.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FORMULAIRE -->
                <form @submit.prevent="submit" class="space-y-5 rounded-2xl bg-white p-6 shadow-card">

                    <div class="mb-2">
                        <h2 class="font-display text-lg font-extrabold text-text-main">
                            Votre pré-inscription
                        </h2>
                        <p class="mt-1 text-sm text-text-sub">
                            Quelques informations pour démarrer
                        </p>
                    </div>

                    <!-- Motivation -->
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Pourquoi voulez-vous participer ? *
                        </label>
                        <textarea v-model="form.motivation_courte" rows="4" required
                                  placeholder="En quelques phrases, expliquez votre motivation..."
                                  class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"
                                  :class="form.errors.motivation_courte ? 'border-red-400' : ''"/>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <p v-if="form.errors.motivation_courte" class="text-red-600">{{ form.errors.motivation_courte }}</p>
                            <p v-else class="text-text-muted">
                                Minimum 20 caractères
                            </p>
                            <p :class="form.motivation_courte.length >= 20 ? 'text-emerald-600 font-bold' : 'text-text-muted'">
                                {{ form.motivation_courte.length }} / 500
                            </p>
                        </div>
                    </div>

                    <!-- Acceptation règlement -->
                    <label class="flex items-start gap-3 rounded-lg border-2 border-border-soft p-4 cursor-pointer hover:bg-page-bg/50">
                        <input v-model="form.reglement_accepte" type="checkbox" required
                               class="mt-0.5 h-5 w-5 rounded border-border-soft text-moov-blue"/>
                        <div class="flex-1 text-sm">
                            <p class="font-bold text-text-main">J'accepte le règlement *</p>
                            <p class="mt-1 text-text-sub">
                                Je certifie l'exactitude des informations et j'accepte les conditions de participation.
                            </p>
                            <a v-if="evenement.reglement_pdf_url"
                               :href="evenement.reglement_pdf_url" target="_blank"
                               class="mt-1.5 inline-block text-xs font-bold text-moov-blue hover:underline">
                                Lire le règlement officiel →
                            </a>
                        </div>
                    </label>

                    <!-- Bouton soumettre -->
                    <button type="submit"
                            :disabled="form.processing || form.motivation_courte.length < 20 || !form.reglement_accepte"
                            class="w-full rounded-lg bg-moov-noir py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="form.processing"> Envoi en cours...</span>
                        <span v-else>Soumettre ma pré-inscription →</span>
                    </button>

                    <p class="text-center text-xs text-text-muted">
                        Vous recevrez un email de confirmation à l'adresse :
                        <strong>{{ $page.props.auth?.user?.email }}</strong>
                    </p>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>