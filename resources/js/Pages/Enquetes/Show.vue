<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: { type: Object, required: true },
    enquete: { type: Object, required: true },
    isManager: { type: Boolean, default: false },
    aDejaRepondu: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
    estParticipantMode: { type: Boolean, default: false },
})

// ─── HELPERS ──────────────────────────
const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    publie: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Publiée' },
    cloture: { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: 'Clôturée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statut })

const labelTypeQuestion = (type) => ({
    note: ' Note',
    choix_unique: ' Choix unique',
    choix_multiple: ' Choix multiples',
    texte_court: ' Texte court',
    texte_long: 'Texte long',
    oui_non: 'Oui/Non',
}[type] || type)

const items = computed(() => props.enquete.questions?.items ?? [])

// ─── FORMULAIRE PARTICIPANT ────────────
const initialReponses = {}
items.value.forEach(item => {
    if (item.type === 'choix_multiple') {
        initialReponses[item.id] = []
    } else if (item.type === 'note') {
        initialReponses[item.id] = null
    } else {
        initialReponses[item.id] = ''
    }
})

const form = useForm({
    reponses: initialReponses,
})

// ─── ACTIONS PARTICIPANT ──────────────
const repondre = () => {
    // Validation des questions obligatoires
    for (const item of items.value) {
        if (item.obligatoire) {
            const valeur = form.reponses[item.id]
            const estVide = valeur === null || valeur === '' || valeur === undefined ||
                (Array.isArray(valeur) && valeur.length === 0)
            if (estVide) {
                alert(`La question "${item.label}" est obligatoire.`)
                return
            }
        }
    }

    const url = props.estParticipantMode
        ? `/mes-enquetes/${props.enquete.id}/repondre`
        : `/evenements/${props.evenement.id}/communication/enquetes/${props.enquete.id}/respond`

    form.post(url, {
        preserveScroll: true,
    })
}

const setNote = (questionId, valeur) => {
    form.reponses[questionId] = valeur
}

// ─── ACTIONS MANAGER ──────────────────
import { router } from '@inertiajs/vue3'

const publier = () => {
    if (!confirm('Publier cette enquête ? Les participants pourront y répondre.')) return
    router.patch(`/evenements/${props.evenement.id}/communication/enquetes/${props.enquete.id}/publish`, {}, {
        preserveScroll: true,
    })
}

const cloturer = () => {
    if (!confirm('Clôturer cette enquête ? Aucune nouvelle réponse ne sera acceptée.')) return
    router.patch(`/evenements/${props.evenement.id}/communication/enquetes/${props.enquete.id}/close`, {}, {
        preserveScroll: true,
    })
}

// ─── PEUT RÉPONDRE ─────────────────────
const peutRepondre = computed(() =>
    !props.isManager &&
    props.enquete.statut === 'publie' &&
    !props.aDejaRepondu
)
</script>

<template>
    <DashboardLayout>

        <!-- ─── RETOUR ─── -->
        <Link :href="estParticipantMode ? '/mes-enquetes' : `/evenements/${evenement.id}/communication/enquetes`"
            class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← {{ estParticipantMode ? 'Retour à mes enquêtes' : 'Retour aux enquêtes' }}
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                        {{ enquete.type || 'enquête' }}
                    </span>
                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                        couleurStatut(enquete.statut).bg, couleurStatut(enquete.statut).text]">
                        <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(enquete.statut).dot]" />
                        {{ couleurStatut(enquete.statut).label }}
                    </span>
                </div>
                <h1 class="mt-2 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    {{ enquete.titre }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                </p>
            </div>

            <!-- Actions MANAGER -->
            <div v-if="isManager" class="flex flex-wrap gap-2">
                <button v-if="enquete.statut === 'brouillon'" @click="publier"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-emerald-700">
                    Publier
                </button>
                <button v-if="enquete.statut === 'publie'" @click="cloturer"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-amber-700">
                    Clôturer
                </button>
                <Link v-if="stats.nb_reponses > 0" :href="`/rapports/enquetes/${enquete.id}/analyse`"
                    class="rounded-lg bg-moov-blue px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-blue-700">
                    📊 Analyser ({{ stats.nb_reponses }})
                </Link>
            </div>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!--   STATS MANAGER                          -->
        <!-- ═══════════════════════════════════════ -->
        <div v-if="isManager" class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Questions</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ items.length }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Réponses reçues</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ stats.nb_reponses ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Statut</p>
                <p class="mt-2 font-display text-xl font-extrabold" :class="couleurStatut(enquete.statut).text">
                    {{ couleurStatut(enquete.statut).label }}
                </p>
            </div>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!--   MESSAGE "DÉJÀ RÉPONDU" (participant)  -->
        <!-- ═══════════════════════════════════════ -->
        <div v-if="!isManager && aDejaRepondu"
            class="mb-6 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-3 font-display text-lg font-extrabold text-emerald-900">
                Merci pour votre retour !
            </h3>
            <p class="mt-1 text-sm text-emerald-700">
                Vous avez déjà répondu à cette enquête. Votre avis a bien été enregistré.
            </p>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!--   MESSAGE "STATUT NON OUVERT"           -->
        <!-- ═══════════════════════════════════════ -->
        <div v-else-if="!isManager && enquete.statut !== 'publie'"
            class="mb-6 rounded-xl border-2 border-amber-200 bg-amber-50 p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-3 font-display text-lg font-extrabold text-amber-900">
                Enquête {{ enquete.statut === 'brouillon' ? 'non publiée' : 'clôturée' }}
            </h3>
            <p class="mt-1 text-sm text-amber-700">
                Cette enquête n'est pas ouverte aux réponses pour le moment.
            </p>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!--   QUESTIONS (vue commune)               -->
        <!-- ═══════════════════════════════════════ -->
        <form v-if="peutRepondre || isManager" @submit.prevent="peutRepondre && repondre()" class="space-y-4">

            <div v-if="isManager" class="rounded-xl bg-blue-50/50 border border-blue-200 p-4">
                <p class="text-sm font-bold text-blue-900">
                    Vue aperçu
                </p>
                <p class="mt-1 text-xs text-blue-700">
                    Vous voyez l'enquête comme les participants la verront. Les champs sont désactivés.
                </p>
            </div>

            <div v-for="(item, qIndex) in items" :key="item.id" class="rounded-xl bg-card p-5 shadow-card">

                <div class="flex items-start gap-3">
                    <span
                        class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-xs font-extrabold text-white">
                        {{ qIndex + 1 }}
                    </span>
                    <div class="flex-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                            {{ labelTypeQuestion(item.type) }}
                        </span>
                        <p class="mt-1 font-bold text-text-main">
                            {{ item.label }}
                            <span v-if="item.obligatoire" class="text-red-600">*</span>
                        </p>
                    </div>
                </div>

                <!-- ─── CHAMP DE RÉPONSE ─── -->
                <div class="mt-4 ml-10">

                    <!-- Note (étoiles) -->
                    <div v-if="item.type === 'note'" class="flex items-center gap-2">
                        <div class="flex gap-1">
                            <button v-for="n in (item.echelle || 5)" :key="n" type="button"
                                @click="!isManager && setNote(item.id, n)" :disabled="isManager" :class="['text-3xl transition',
                                    (form.reponses[item.id] && form.reponses[item.id] >= n)
                                        ? 'text-amber-400'
                                        : 'text-slate-300',
                                    !isManager && 'hover:scale-110 cursor-pointer']">
                                ★
                            </button>
                        </div>
                        <span v-if="form.reponses[item.id]" class="ml-2 text-sm font-bold text-text-sub">
                            {{ form.reponses[item.id] }} / {{ item.echelle }}
                        </span>
                    </div>

                    <!-- Choix unique -->
                    <div v-else-if="item.type === 'choix_unique'" class="space-y-2">
                        <label v-for="option in item.options" :key="option"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-border-soft bg-white p-3 transition hover:border-moov-blue">
                            <input type="radio" :name="item.id" :value="option" v-model="form.reponses[item.id]"
                                :disabled="isManager" class="h-4 w-4 text-moov-blue focus:ring-moov-blue" />
                            <span class="text-sm font-medium text-text-main">{{ option }}</span>
                        </label>
                    </div>

                    <!-- Choix multiple -->
                    <div v-else-if="item.type === 'choix_multiple'" class="space-y-2">
                        <label v-for="option in item.options" :key="option"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-border-soft bg-white p-3 transition hover:border-moov-blue">
                            <input type="checkbox" :value="option" v-model="form.reponses[item.id]"
                                :disabled="isManager" class="h-4 w-4 rounded text-moov-blue focus:ring-moov-blue" />
                            <span class="text-sm font-medium text-text-main">{{ option }}</span>
                        </label>
                    </div>

                    <!-- Texte court -->
                    <input v-else-if="item.type === 'texte_court'" type="text" v-model="form.reponses[item.id]"
                        :disabled="isManager" placeholder="Votre réponse..."
                        class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue disabled:bg-page-bg disabled:cursor-not-allowed" />

                    <!-- Texte long -->
                    <textarea v-else-if="item.type === 'texte_long'" v-model="form.reponses[item.id]"
                        :disabled="isManager" rows="4" placeholder="Détaillez votre réponse..."
                        class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue disabled:bg-page-bg disabled:cursor-not-allowed" />

                    <!-- Oui / Non -->
                    <div v-else-if="item.type === 'oui_non'" class="flex gap-3">
                        <button type="button" @click="!isManager && (form.reponses[item.id] = 'oui')"
                            :disabled="isManager" :class="['flex items-center gap-2 rounded-lg border-2 px-5 py-2.5 text-sm font-bold transition',
                                form.reponses[item.id] === 'oui'
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                    : 'border-border-soft bg-white text-text-sub hover:border-emerald-300']">
                            Oui
                        </button>
                        <button type="button" @click="!isManager && (form.reponses[item.id] = 'non')"
                            :disabled="isManager" :class="['flex items-center gap-2 rounded-lg border-2 px-5 py-2.5 text-sm font-bold transition',
                                form.reponses[item.id] === 'non'
                                    ? 'border-red-500 bg-red-50 text-red-700'
                                    : 'border-border-soft bg-white text-text-sub hover:border-red-300']">
                            Non
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── BOUTON ENVOYER (uniquement participant) ─── -->
            <div v-if="peutRepondre" class="flex justify-end pt-4">
                <button type="submit" :disabled="form.processing"
                    class="rounded-lg bg-moov-blue px-8 py-3 text-base font-bold text-white shadow-md transition hover:bg-blue-700 disabled:opacity-50">
                    {{ form.processing ? 'Envoi...' : '📤 Envoyer mes réponses' }}
                </button>
            </div>
        </form>

    </DashboardLayout>
</template>