<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    inscription: { type: Object, required: true },
    userRole: { type: Object, required: true },
})

// Helpers
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const couleurStatut = (statut) => ({
    preinscrit: { bg: 'bg-amber-100', text: 'text-amber-700' },
    preselectionne: { bg: 'bg-blue-100', text: 'text-blue-700' },
    dossier_soumis: { bg: 'bg-indigo-100', text: 'text-indigo-700' },
    en_analyse: { bg: 'bg-violet-100', text: 'text-violet-700' },
    recommandee: { bg: 'bg-cyan-100', text: 'text-cyan-700' },
    acceptee: { bg: 'bg-emerald-100', text: 'text-emerald-700' },
    confirmee: { bg: 'bg-emerald-200', text: 'text-emerald-900' },
    refusee: { bg: 'bg-red-100', text: 'text-red-700' },
    present: { bg: 'bg-emerald-200', text: 'text-emerald-900' },
    annulee: { bg: 'bg-slate-100', text: 'text-slate-600' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600' })

const labelStatut = (statut) => ({
    preinscrit: 'Pré-inscrit', preselectionne: 'Présélectionné',
    dossier_soumis: 'Dossier soumis', en_analyse: 'En analyse',
    recommandee: 'Recommandée', acceptee: 'Acceptée',
    confirmee: 'Confirmée', refusee: 'Refusée',
    present: 'Présent', annulee: 'Annulée',
}[statut] || statut)

// ──── ACTIONS WORKFLOW ────
const typeCode = computed(() => props.inscription.evenement?.type_evenement?.code)
const necessitePreselection = computed(() => !['CONF', 'FORMATION'].includes(typeCode.value))

// Présélection (Niveau 1 → Niveau 2)
const peutPreselectionner = computed(() =>
    props.inscription.statut === 'preinscrit' && necessitePreselection.value
)

const presele = () => {
    if (confirm('Présélectionner ce candidat et lui envoyer le lien du dossier complet ?')) {
        router.post(`/inscriptions/${props.inscription.id}/preselectionner`, {}, {
            preserveScroll: true,
        })
    }
}

// Recommandation (Organisateur)
const peutRecommander = computed(() =>
    ['dossier_soumis', 'en_analyse'].includes(props.inscription.statut)
)
const modalRecommandation = ref(false)
const noteOrganisateur = ref('')

const recommander = () => {
    router.post(`/inscriptions/${props.inscription.id}/recommander`, {
        note_organisateur: noteOrganisateur.value,
    }, {
        onSuccess: () => modalRecommandation.value = false,
        preserveScroll: true,
    })
}

// Validation finale (Responsable)
const peutValider = computed(() => {
    if (!props.userRole.estResponsable) return false
    if (!necessitePreselection.value) return props.inscription.statut === 'preinscrit'
    return props.inscription.statut === 'recommandee'
})

const valider = () => {
    if (confirm('Valider définitivement cette inscription ? Un email avec le QR code sera envoyé.')) {
        router.post(`/inscriptions/${props.inscription.id}/valider`, {}, {
            preserveScroll: true,
        })
    }
}

// Refus
const peutRefuser = computed(() =>
    ['preinscrit', 'preselectionne', 'dossier_soumis', 'en_analyse', 'recommandee']
        .includes(props.inscription.statut)
)
const modalRefus = ref(false)
const motifRefus = ref('')

const ouvrirModalRefus = () => {
    motifRefus.value = ''
    modalRefus.value = true
}

const confirmerRefus = () => {
    if (motifRefus.value.length < 10) {
        alert('Le motif doit contenir au moins 10 caractères')
        return
    }
    router.post(`/inscriptions/${props.inscription.id}/refuser`, {
        motif_refus: motifRefus.value,
    }, {
        onSuccess: () => modalRefus.value = false,
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

        <!-- Retour -->
        <Link href="/inscriptions"
            class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Toutes les inscriptions
        </Link>

        <!-- En-tête -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Dossier d'inscription
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main">
                    {{ inscription.user?.prenom }} {{ inscription.user?.nom }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ inscription.user?.email }}
                    <span v-if="inscription.user?.telephone"> · {{ inscription.user.telephone }}</span>
                </p>
            </div>

            <span :class="['rounded-full px-4 py-1.5 text-sm font-bold',
                couleurStatut(inscription.statut).bg, couleurStatut(inscription.statut).text]">
                {{ labelStatut(inscription.statut) }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- COLONNE PRINCIPALE -->
            <div class="space-y-6 lg:col-span-2">

                <!-- Infos événement -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Événement
                        </p>
                        <h2 class="mt-1 font-display text-xl font-extrabold text-text-main">
                            {{ inscription.evenement?.titre }}
                        </h2>
                        <p class="mt-1 text-sm text-text-sub">
                            {{ inscription.evenement?.type_evenement?.nom }} · {{
                                formaterDate(inscription.evenement?.date_debut) }}
                        </p>
                    </div>
                </div>

                <!-- Motivation -->
                <div v-if="inscription.motivation" class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Motivation du candidat
                        </h3>
                    </div>
                    <div class="p-5">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-text-main">
                            {{ inscription.motivation }}
                        </p>
                    </div>
                </div>

                <!-- Dossier complet (Niveau 2) -->
                <div v-if="inscription.niveau_inscription === 'niveau_2'" class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Dossier complet
                        </h3>
                    </div>
                    <div class="space-y-3 p-5 text-sm">

                        <!-- BARA MOUSSO -->
                        <template v-if="typeCode === 'BARA_MOUSSO'">
                            <div v-if="inscription.localite" class="flex justify-between">
                                <span class="text-text-sub">Localité</span>
                                <strong class="text-text-main">{{ inscription.localite }}</strong>
                            </div>
                            <div v-if="inscription.domaine_activite" class="flex justify-between">
                                <span class="text-text-sub">Domaine</span>
                                <strong class="text-text-main">{{ inscription.domaine_activite }}</strong>
                            </div>
                            <div v-if="inscription.effectif_employe" class="flex justify-between">
                                <span class="text-text-sub">Effectif</span>
                                <strong class="text-text-main">{{ inscription.effectif_employe }}</strong>
                            </div>
                            <div v-if="inscription.besoins_financiers" class="flex justify-between">
                                <span class="text-text-sub">Besoins financiers</span>
                                <strong class="text-text-main">{{
                                    Number(inscription.besoins_financiers).toLocaleString('fr-FR') }} FCFA</strong>
                            </div>
                            <div v-if="inscription.description_projet">
                                <p class="text-xs font-bold text-text-sub">Description du projet</p>
                                <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.description_projet }}
                                </p>
                            </div>
                            <a v-if="inscription.url_video_pitch" :href="inscription.url_video_pitch" target="_blank"
                                class="inline-block rounded-lg bg-moov-blue px-3 py-1.5 text-xs font-bold text-white">
                                Voir la vidéo pitch
                            </a>
                        </template>

                        <!-- SPORT -->
                        <template v-else-if="typeCode === 'SPORT'">
                            <div v-if="inscription.nom_equipe" class="flex justify-between">
                                <span class="text-text-sub">Équipe</span>
                                <strong class="text-text-main">{{ inscription.nom_equipe }}</strong>
                            </div>
                            <div v-if="inscription.capitaine" class="flex justify-between">
                                <span class="text-text-sub">Capitaine</span>
                                <strong class="text-text-main">{{ inscription.capitaine }}</strong>
                            </div>
                            <div v-if="inscription.categorie_age" class="flex justify-between">
                                <span class="text-text-sub">Catégorie</span>
                                <strong class="text-text-main">{{ inscription.categorie_age }}</strong>
                            </div>
                            <div v-if="inscription.effectif_equipe" class="flex justify-between">
                                <span class="text-text-sub">Effectif</span>
                                <strong class="text-text-main">{{ inscription.effectif_equipe }} joueurs</strong>
                            </div>
                            <div v-if="inscription.coach_nom" class="flex justify-between">
                                <span class="text-text-sub">Coach</span>
                                <strong class="text-text-main">{{ inscription.coach_nom }}</strong>
                            </div>
                        </template>

                        <!-- HACK -->
                        <template v-else-if="typeCode === 'HACK'">
                            <div v-if="inscription.nom_equipe" class="flex justify-between">
                                <span class="text-text-sub">Équipe</span>
                                <strong class="text-text-main">{{ inscription.nom_equipe }}</strong>
                            </div>
                            <div v-if="inscription.niveau_equipe" class="flex justify-between">
                                <span class="text-text-sub">Niveau</span>
                                <strong class="text-text-main">{{ inscription.niveau_equipe }}</strong>
                            </div>
                            <div v-if="inscription.technologies">
                                <p class="text-xs font-bold text-text-sub">Technologies</p>
                                <p class="mt-1 text-text-main">{{ inscription.technologies }}</p>
                            </div>
                            <div v-if="inscription.idee">
                                <p class="text-xs font-bold text-text-sub">Idée / Pitch</p>
                                <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.idee }}</p>
                            </div>
                            <a v-if="inscription.url_portfolio" :href="inscription.url_portfolio" target="_blank"
                                class="inline-block rounded-lg bg-moov-blue px-3 py-1.5 text-xs font-bold text-white">
                                Portfolio / GitHub
                            </a>
                        </template>

                        <!-- CHALLENGE -->
                        <template v-else-if="typeCode === 'CHALLENGE'">
                            <div v-if="inscription.titre_idee" class="flex justify-between">
                                <span class="text-text-sub">Titre de l'idée</span>
                                <strong class="text-text-main">{{ inscription.titre_idee }}</strong>
                            </div>
                            <div v-if="inscription.stade_maturite" class="flex justify-between">
                                <span class="text-text-sub">Stade de maturité</span>
                                <strong class="text-text-main">{{ inscription.stade_maturite }}</strong>
                            </div>
                            <div v-if="inscription.marche_vise" class="flex justify-between">
                                <span class="text-text-sub">Marché visé</span>
                                <strong class="text-text-main">{{ inscription.marche_vise }}</strong>
                            </div>
                            <div v-if="inscription.statut_juridique" class="flex justify-between">
                                <span class="text-text-sub">Statut juridique</span>
                                <strong class="text-text-main">{{ inscription.statut_juridique }}</strong>
                            </div>
                            <div v-if="inscription.investissement_requis" class="flex justify-between">
                                <span class="text-text-sub">Investissement requis</span>
                                <strong class="text-text-main">{{
                                    Number(inscription.investissement_requis).toLocaleString('fr-FR') }} FCFA</strong>
                            </div>
                            <div v-if="inscription.description">
                                <p class="text-xs font-bold text-text-sub">Description</p>
                                <p class="mt-1 whitespace-pre-line text-text-main">{{ inscription.description }}</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Note organisateur (si recommandée) -->
                <div v-if="inscription.note_organisateur" class="rounded-xl border border-cyan-200 bg-cyan-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">
                        Note de l'organisateur
                    </p>
                    <p class="mt-2 text-sm text-cyan-900">{{ inscription.note_organisateur }}</p>
                    <p v-if="inscription.recommande_par_user" class="mt-2 text-xs text-cyan-700">
                        Par {{ inscription.recommande_par_user.prenom }} {{ inscription.recommande_par_user.nom }}
                    </p>
                </div>

                <!-- Motif refus -->
                <div v-if="inscription.statut === 'refusee' && inscription.motif_refus"
                    class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                        Motif du refus
                    </p>
                    <p class="mt-2 text-sm text-red-900">{{ inscription.motif_refus }}</p>
                </div>
            </div>

            <!-- SIDEBAR : ACTIONS -->
            <aside class="space-y-4">

                <!-- ACTIONS WORKFLOW -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Actions
                        </h3>
                    </div>

                    <div class="space-y-2 p-5">

                        <!-- Présélectionner -->
                        <button v-if="peutPreselectionner" @click="presele"
                            class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                            Présélectionner
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Envoyer le lien du dossier complet
                            </span>
                        </button>

                        <!-- Recommander (Organisateur) -->
                        <button v-if="peutRecommander" @click="modalRecommandation = true"
                            class="w-full rounded-lg bg-cyan-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-cyan-700">
                            Recommander
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Avis favorable pour validation finale
                            </span>
                        </button>

                        <!-- Valider (Responsable) -->
                        <button v-if="peutValider" @click="valider"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                            ✓ Valider définitivement
                            <span class="block mt-1 text-xs font-normal opacity-75">
                                Acceptation finale + QR code
                            </span>
                        </button>

                        <!-- Refuser -->
                        <button v-if="peutRefuser" @click="ouvrirModalRefus"
                            class="w-full rounded-lg border-2 border-red-300 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50">
                            ✗ Refuser
                        </button>

                        <!-- Aucune action -->
                        <p v-if="!peutPreselectionner && !peutRecommander && !peutValider && !peutRefuser"
                            class="text-center text-xs text-text-muted py-3">
                            Aucune action disponible
                        </p>
                    </div>
                </div>

                <!-- HISTORIQUE -->
                <div class="rounded-xl bg-white shadow-card">
                    <div class="border-b border-border-soft p-5">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wider text-text-sub">
                            Historique
                        </h3>
                    </div>
                    <div class="space-y-3 p-5 text-xs">

                        <div>
                            <p class="font-bold text-text-main">Soumis</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.created_at) }}</p>
                        </div>

                        <div v-if="inscription.presele_le">
                            <p class="font-bold text-text-main">Présélectionné</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.presele_le) }}</p>
                            <p v-if="inscription.presele_par_user" class="text-text-muted">
                                par {{ inscription.presele_par_user.prenom }} {{ inscription.presele_par_user.nom }}
                            </p>
                        </div>

                        <div v-if="inscription.recommande_le">
                            <p class="font-bold text-text-main">Recommandé</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.recommande_le) }}</p>
                            <p v-if="inscription.recommande_par_user" class="text-text-muted">
                                par {{ inscription.recommande_par_user.prenom }} {{ inscription.recommande_par_user.nom
                                }}
                            </p>
                        </div>

                        <div v-if="inscription.valide_le">
                            <p class="font-bold text-emerald-700">Validé</p>
                            <p class="text-text-sub">{{ formaterDate(inscription.valide_le) }}</p>
                            <p v-if="inscription.valide_par_user" class="text-text-muted">
                                par {{ inscription.valide_par_user.prenom }} {{ inscription.valide_par_user.nom }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- QR CODE si confirmé -->
                <div v-if="['confirmee', 'acceptee', 'present'].includes(inscription.statut)"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                        Badge participant
                    </p>
                    <p class="mt-2 font-mono text-xl font-extrabold text-emerald-900 tracking-wider">
                        {{ inscription.qr_code }}
                    </p>
                </div>
            </aside>
        </div>

        <!-- ════════ MODALE RECOMMANDATION ════════ -->
        <div v-if="modalRecommandation" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="modalRecommandation = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Recommander cette candidature
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Votre avis sera pris en compte par le responsable dCIRP pour la validation finale.
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Note ou commentaire (optionnel)
                    </label>
                    <textarea v-model="noteOrganisateur" rows="4" placeholder="Pourquoi recommandez-vous ce candidat ?"
                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRecommandation = false"
                        class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="recommander"
                        class="rounded-lg bg-cyan-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-cyan-700">
                        Recommander
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════ MODALE REFUS ════════ -->
        <div v-if="modalRefus" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="modalRefus = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Refuser cette candidature
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Indiquez le motif - il sera communiqué au candidat par email.
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Motif * (min. 10 caractères)
                    </label>
                    <textarea v-model="motifRefus" rows="4"
                        placeholder="Ex: Le profil ne correspond pas aux critères du concours..."
                        class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue" />
                    <p class="mt-1 text-xs text-text-muted">{{ motifRefus.length }} caractères</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRefus = false"
                        class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRefus" :disabled="motifRefus.length < 10"
                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-50">
                        Refuser et notifier
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>