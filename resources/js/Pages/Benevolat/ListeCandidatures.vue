<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    poste: { type: Object, required: true },
    stats: { type: Object, required: true },
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const couleurStatut = (statut) => ({
    candidat: { bg: 'bg-amber-100',    text: 'text-amber-700',   label: 'En attente' },
    accepte:  { bg: 'bg-emerald-100',  text: 'text-emerald-700', label: 'Acceptée' },
    refuse:   { bg: 'bg-red-100',      text: 'text-red-700',     label: 'Refusée' },
    annule:   { bg: 'bg-slate-100',    text: 'text-slate-600',   label: 'Annulée' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', label: statut })

const accepter = (c) => {
    if (!confirm(`Accepter la candidature de ${c.user.prenom} ${c.user.nom} ?`)) return
    router.post(`/candidatures-benevoles/${c.id}/accepter`, {}, { preserveScroll: true })
}

const modalRefus = ref(false)
const candidatureRefus = ref(null)
const motifRefus = ref('')

const ouvrirRefus = (c) => {
    candidatureRefus.value = c
    motifRefus.value = ''
    modalRefus.value = true
}

const confirmerRefus = () => {
    if (motifRefus.value.length < 10) {
        alert('Le motif doit contenir au moins 10 caractères')
        return
    }
    router.post(`/candidatures-benevoles/${candidatureRefus.value.id}/refuser`, {
        motif_refus: motifRefus.value,
    }, {
        onSuccess: () => modalRefus.value = false,
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

        <Link :href="`/evenements/${poste.evenement.id}/logistique`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la logistique
        </Link>

        <!-- En-tête -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Candidatures bénévoles · {{ poste.evenement.titre }}
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main">
                {{ poste.nom_poste }}
            </h1>
            <p class="mt-1 text-sm text-text-sub">{{ poste.description }}</p>
        </div>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-text-main">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">En attente</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-amber-600">{{ stats.en_attente }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Acceptées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.acceptees }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Refusées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-red-600">{{ stats.refusees }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Places restantes</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                    {{ stats.places_restantes }} / {{ poste.places_max }}
                </p>
            </div>
        </div>

        
        <div v-if="poste.candidatures.length > 0" class="space-y-3">
            <div v-for="c in poste.candidatures" :key="c.id"
                 class="rounded-xl bg-white p-6 shadow-card">

                <div class="flex flex-wrap items-start justify-between gap-4">

                    <!-- Infos candidat -->
                    <div class="min-w-0 flex-1">
                        <h3 class="font-display text-base font-extrabold text-text-main">
                            {{ c.user.prenom }} {{ c.user.nom }}
                        </h3>
                        <p class="mt-1 text-xs text-text-sub">
                            {{ c.user.email }}
                            <span v-if="c.user.telephone"> · {{ c.user.telephone }}</span>
                        </p>

                        <div class="mt-4 space-y-3 text-sm">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Motivation</p>
                                <p class="mt-1 whitespace-pre-line text-text-main">{{ c.motivation }}</p>
                            </div>

                            <div v-if="c.experience">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Expérience</p>
                                <p class="mt-1 whitespace-pre-line text-text-main">{{ c.experience }}</p>
                            </div>

                            <div v-if="c.disponibilites">
                                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Disponibilités</p>
                                <p class="mt-1 text-text-main">{{ c.disponibilites }}</p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-text-muted">
                            Postulée le {{ formaterDate(c.created_at) }}
                        </p>

                       
                        <div v-if="c.statut === 'refuse' && c.motif_refus"
                             class="mt-3 rounded-lg bg-red-50 border-l-4 border-red-500 p-3">
                            <p class="text-xs font-bold text-red-700">Motif du refus :</p>
                            <p class="mt-1 text-xs text-red-800">{{ c.motif_refus }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col items-end gap-2">
                        <span :class="['rounded-full px-3 py-1 text-xs font-bold',
                            couleurStatut(c.statut).bg, couleurStatut(c.statut).text]">
                            {{ couleurStatut(c.statut).label }}
                        </span>

                        <div v-if="c.statut === 'candidat'" class="flex gap-2">
                            <button @click="accepter(c)"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                                ✓ Accepter
                            </button>
                            <button @click="ouvrirRefus(c)"
                                    class="rounded-lg border-2 border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                Refuser
                            </button>
                        </div>

                        <p v-if="c.valide_par_user" class="text-[10px] text-text-muted">
                            Décision par : {{ c.valide_par_user.prenom }} {{ c.valide_par_user.nom }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- État vide -->
        <div v-else class="rounded-xl border-2 border-dashed border-border-soft bg-white p-12 text-center">
            <p class="font-bold text-text-main">Aucune candidature pour ce poste</p>
            <p class="mt-1 text-sm text-text-sub">Les participants pourront postuler depuis la page de l'événement</p>
        </div>

       
        <div v-if="modalRefus"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalRefus = false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Refuser la candidature</h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Indiquez le motif - il sera communiqué au candidat
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Motif * (min. 10 caractères)
                    </label>
                    <textarea v-model="motifRefus" rows="4"
                              placeholder="Ex: Le profil ne correspond pas aux compétences requises..."
                              class="w-full rounded-lg border-2 border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    <p class="mt-1 text-xs text-text-muted">{{ motifRefus.length }} caractères</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRefus = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRefus"
                            :disabled="motifRefus.length < 10"
                            class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-50">
                        Refuser
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>