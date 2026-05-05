<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    inscription: Object,
})

const statutInfo = computed(() => ({
    en_attente: {
        couleur: 'amber',
        titre:   'Dossier soumis avec succès',
        message: 'Votre dossier a été reçu et sera analysé par l\'organisateur.',
        icone:   '⏳',
    },
    en_analyse: {
        couleur: 'blue',
        titre:   'Dossier en cours d\'analyse',
        message: 'L\'organisateur étudie actuellement votre candidature.',
        icone:   '🔍',
    },
    acceptee: {
        couleur: 'emerald',
        titre:   'Dossier accepté !',
        message: 'Votre candidature a été retenue. En attente de paiement.',
        icone:   '✓',
    },
    confirmee: {
        couleur: 'emerald',
        titre:   'Inscription confirmée',
        message: 'Votre place est garantie. À très bientôt !',
        icone:   '✓',
    },
    refusee: {
        couleur: 'red',
        titre:   'Dossier non retenu',
        message: props.inscription?.motif_refus ?? 'Votre dossier n\'a pas été retenu cette fois.',
        icone:   '✗',
    },
}[props.inscription?.statut] || {
    couleur: 'slate', titre: 'Statut', message: '', icone: '?',
}))
</script>

<template>
    <PublicLayout>
        <div class="mx-auto max-w-2xl px-4 py-12">

            <!-- Carte principale -->
            <div class="rounded-xl bg-card p-8 text-center shadow-card">

                <!-- Icône statut -->
                <div :class="['mx-auto flex h-20 w-20 items-center justify-center rounded-full',
                    `bg-${statutInfo.couleur}-100`]">
                    <span :class="['font-display text-3xl', `text-${statutInfo.couleur}-700`]">
                        {{ statutInfo.icone }}
                    </span>
                </div>

                <h1 class="mt-6 font-display text-3xl font-extrabold text-text-main">
                    {{ statutInfo.titre }}
                </h1>
                <p class="mt-3 text-base text-text-sub">
                    {{ statutInfo.message }}
                </p>

                <!-- Référence -->
                <div class="mt-8 rounded-lg bg-page-bg p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Référence du dossier
                    </p>
                    <p class="mt-2 font-display text-2xl font-extrabold tracking-wider text-text-main">
                        {{ inscription.qr_code }}
                    </p>
                </div>

                <!-- Événement -->
                <div class="mt-6 border-t border-border-soft pt-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Événement
                    </p>
                    <p class="mt-2 font-bold text-text-main">{{ inscription.evenement?.titre }}</p>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Link href="/evenements"
                          class="rounded-lg border border-border-soft px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Retour aux événements
                    </Link>
                    <Link v-if="inscription.evenement"
                          :href="`/evenements/${inscription.evenement.id}`"
                          class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white transition hover:bg-moov-noir-soft">
                        Voir l'événement
                    </Link>
                </div>
            </div>

            <!-- Notification email -->
            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-center">
                <p class="text-sm text-blue-900">
                    Vous serez notifié(e) par email à chaque étape du traitement de votre dossier.
                </p>
            </div>

        </div>
    </PublicLayout>
</template>