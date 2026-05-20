<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement: { type: Object, required: true },
    campagne:  { type: Object, required: true },
    envois:    { type: Array, default: () => [] },
    stats:     { type: Object, default: () => ({}) },
    apercu:    { type: Object, default: () => ({}) },
    cibles:    { type: Object, default: () => ({}) },
})

// ─── ONGLETS ──────────────────────
const ongletActif = ref('apercu')

// ─── HELPERS ──────────────────────
const formatDateTime = (d) => d
    ? new Date(d).toLocaleString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
    : '—'

const couleurStatut = (statut) => ({
    brouillon: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'Brouillon' },
    envoyee:   { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Envoyée' },
    erreur:    { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Erreur' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', label: statut })

const couleurEnvoi = (statut) => ({
    sent:   { bg: 'bg-emerald-50', text: 'text-emerald-700', label: '✓ Envoyé' },
    failed: { bg: 'bg-red-50', text: 'text-red-700', label: '✕ Échec' },
    queued: { bg: 'bg-amber-50', text: 'text-amber-700', label: '⏳ En attente' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', label: statut })

const initiales = (user) =>
    `${user?.prenom?.[0] ?? ''}${user?.nom?.[0] ?? ''}`.toUpperCase() || 'U'

// ─── ACTIONS ──────────────────────
const envoyer = () => {
    if (!confirm(`Envoyer cette campagne maintenant ?\nCette action est immédiate et irréversible.`)) return
    router.post(`/evenements/${props.evenement.id}/communication/campaigns/${props.campagne.id}/send`, {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <DashboardLayout>

        <Link :href="`/evenements/${evenement.id}/communication/campaigns`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
             Retour aux campagnes
        </Link>

        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                        couleurStatut(campagne.statut).bg, couleurStatut(campagne.statut).text]">
                        <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(campagne.statut).dot]"/>
                        {{ couleurStatut(campagne.statut).label }}
                    </span>
                    <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                         {{ cibles[campagne.mode_destinataires] || campagne.mode_destinataires }}
                    </span>
                </div>
                <h1 class="mt-2 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                    {{ campagne.objet }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                </p>
                <p v-if="campagne.date_envoi" class="mt-1 text-xs text-text-muted">
                    Envoyée le {{ formatDateTime(campagne.date_envoi) }}
                </p>
                <p v-else class="mt-1 text-xs text-text-muted">
                    Créée le {{ formatDateTime(campagne.created_at) }}
                </p>
            </div>

            <button v-if="campagne.statut === 'brouillon'" @click="envoyer"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Envoyer maintenant
            </button>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total envois</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ stats.total ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Envoyés</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ stats.nb_envoyes ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Erreurs</p>
                <p class="mt-2 font-display text-3xl font-extrabold"
                   :class="(stats.nb_erreurs ?? 0) > 0 ? 'text-red-600' : 'text-slate-400'">
                    {{ stats.nb_erreurs ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Taux de réussite</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-purple-600">
                    {{ stats.total > 0 ? Math.round((stats.nb_envoyes / stats.total) * 100) : 0 }}%
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-card shadow-card">

            <div class="border-b border-border-soft">
                <nav class="flex gap-1 px-4">
                    <button @click="ongletActif = 'apercu'"
                            :class="['flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition',
                                ongletActif === 'apercu'
                                    ? 'border-moov-blue text-moov-blue'
                                    : 'border-transparent text-text-sub hover:text-text-main']">
                         Aperçu
                    </button>
                    <button @click="ongletActif = 'historique'"
                            :class="['flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition',
                                ongletActif === 'historique'
                                    ? 'border-moov-blue text-moov-blue'
                                    : 'border-transparent text-text-sub hover:text-text-main']">
                         Historique des envois
                        <span :class="['rounded-full px-2 py-0.5 text-[10px] font-extrabold',
                            ongletActif === 'historique' ? 'bg-moov-blue text-white' : 'bg-page-bg text-text-muted']">
                            {{ envois.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ═════ ONGLET APERÇU ═════ -->
            <div v-if="ongletActif === 'apercu'" class="p-6">

                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-display text-sm font-extrabold text-text-main">
                        Tel que vu par les participants
                    </h3>
                    <span class="text-xs text-text-muted">
                        Avec variables remplies pour un participant test
                    </span>
                </div>

                <!-- Email simulé -->
                <div class="mx-auto max-w-2xl rounded-xl border-2 border-border-soft bg-white shadow-card">

                   
                    <div class="border-b border-border-soft p-4">
                        <div class="grid grid-cols-[80px_1fr] gap-2 text-sm">
                            <p class="font-bold text-text-muted">De :</p>
                            <p class="text-text-main">MoovFlow &lt;{{ apercu.destinataire ? 'no-reply@moov.bf' : '...' }}&gt;</p>

                            <p class="font-bold text-text-muted">À :</p>
                            <p class="font-mono text-text-main">{{ apercu.destinataire || '—' }}</p>

                            <p class="font-bold text-text-muted">Objet :</p>
                            <p class="font-bold text-text-main">{{ apercu.objet || campagne.objet }}</p>
                        </div>
                    </div>

                    <!-- Corps -->
                    <div class="p-6">
                        <pre class="whitespace-pre-wrap text-sm text-text-main font-sans leading-relaxed">{{ apercu.corps || campagne.contenu }}</pre>
                    </div>
                </div>

                <p class="mt-3 text-center text-xs italic text-text-muted">
                     Cet aperçu utilise les données d'un participant test pour montrer comment les variables sont remplies
                </p>
            </div>

            
            <div v-else-if="ongletActif === 'historique'" class="p-5">

                <div v-if="envois.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-3 font-bold text-text-main">Aucun envoi pour le moment</p>
                    <p class="mt-1 text-sm text-text-sub">
                        {{ campagne.statut === 'brouillon' ? 'Cliquez sur "Envoyer maintenant" pour lancer la campagne.' : 'L\'historique apparaîtra ici après envoi.' }}
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-soft text-sm">
                        <thead class="bg-page-bg/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Date d'envoi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Destinataire</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Détails</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft">
                            <tr v-for="envoi in envois" :key="envoi.id"
                                class="transition hover:bg-page-bg/30">

                                <td class="px-4 py-3 text-text-main">
                                    <p>{{ formatDateTime(envoi.envoye_at || envoi.created_at) }}</p>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div v-if="envoi.user"
                                             class="flex h-8 w-8 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                            {{ initiales(envoi.user) }}
                                        </div>
                                        <div v-else
                                             class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">
                                            ?
                                        </div>
                                        <div>
                                            <p v-if="envoi.user" class="font-bold text-text-main">
                                                {{ envoi.user.prenom }} {{ envoi.user.nom }}
                                            </p>
                                            <p class="text-xs font-mono text-text-muted">{{ envoi.destinataire }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                        couleurEnvoi(envoi.statut).bg, couleurEnvoi(envoi.statut).text]">
                                        {{ couleurEnvoi(envoi.statut).label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-xs">
                                    <p v-if="envoi.erreur" class="text-red-600 italic">{{ envoi.erreur }}</p>
                                    <p v-else-if="envoi.statut === 'sent'" class="text-text-muted">Email transmis avec succès</p>
                                    <p v-else class="text-text-muted">—</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>