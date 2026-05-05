<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    inscriptions: Object,
    stats:        Object,
    evenements:   Array,
    filters:      Object,
})

// ── FILTRES ──────────────────────────────
const filtreStatut    = ref(props.filters?.statut ?? '')
const filtreEvenement = ref(props.filters?.evenement_id ?? '')
const recherche       = ref(props.filters?.search ?? '')

const filtrer = () => {
    router.get('/inscriptions', {
        statut:       filtreStatut.value || undefined,
        evenement_id: filtreEvenement.value || undefined,
        search:       recherche.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}

const reinitialiser = () => {
    filtreStatut.value = ''
    filtreEvenement.value = ''
    recherche.value = ''
    router.get('/inscriptions')
}

// ── HELPERS ──────────────────────────────
const couleurStatut = (statut) => ({
    en_attente:  { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-500',    label: 'En attente' },
    en_analyse:  { bg: 'bg-blue-50',     text: 'text-blue-700',     dot: 'bg-blue-500',     label: 'En analyse' },
    acceptee:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  dot: 'bg-emerald-500',  label: 'Acceptée' },
    confirmee:   { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Confirmée' },
    refusee:     { bg: 'bg-red-50',      text: 'text-red-700',      dot: 'bg-red-500',      label: 'Refusée' },
    annulee:     { bg: 'bg-slate-100',   text: 'text-slate-600',    dot: 'bg-slate-400',    label: 'Annulée' },
    present:     { bg: 'bg-emerald-100', text: 'text-emerald-800',  dot: 'bg-emerald-600',  label: 'Présent' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400', label: statut })

const couleurType = (code) => ({
    BARA_MOUSSO: 'bg-amber-50 text-amber-700',
    CONF:        'bg-rose-50 text-rose-700',
    SPORT:       'bg-blue-50 text-blue-700',
    CHALLENGE:   'bg-violet-50 text-violet-700',
    FORMATION:   'bg-emerald-50 text-emerald-700',
    HACK:        'bg-orange-50 text-orange-700',
    SALON:       'bg-indigo-50 text-indigo-700',
}[code] || 'bg-slate-50 text-slate-700')

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const formaterHeure = (d) => {
    if (!d) return ''
    return new Date(d).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
    })
}

// ── ACTIONS RAPIDES ──────────────────────
const analyser = (id) => {
    if (confirm('Marquer ce dossier comme "en cours d\'analyse" ?')) {
        router.post(`/inscriptions/${id}/analyser`)
    }
}

const accepter = (id) => {
    if (confirm('Accepter ce dossier ?')) {
        router.post(`/inscriptions/${id}/accepter`)
    }
}

// ── MODALE DE REFUS ──────────────────────
const inscriptionARefuser = ref(null)
const motifRefus = ref('')
const erreurMotif = ref('')

const ouvrirModalRefus = (insc) => {
    inscriptionARefuser.value = insc
    motifRefus.value = ''
    erreurMotif.value = ''
}

const fermerModalRefus = () => {
    inscriptionARefuser.value = null
    motifRefus.value = ''
    erreurMotif.value = ''
}

const confirmerRefus = () => {
    if (motifRefus.value.trim().length < 10) {
        erreurMotif.value = 'Le motif doit comporter au moins 10 caractères.'
        return
    }
    router.post(`/inscriptions/${inscriptionARefuser.value.id}/refuser`, {
        motif_refus: motifRefus.value,
    }, {
        onSuccess: () => fermerModalRefus(),
    })
}
</script>

<template>
    <DashboardLayout>

        <!-- En-tête -->
        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold text-text-main">
                Gestion des dossiers
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Analyse et validation des candidatures aux événements
            </p>
        </div>

        <!-- KPIs par statut -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
            <button @click="filtreStatut = ''; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        !filtreStatut ? 'ring-2 ring-moov-blue' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">{{ stats.total }}</p>
            </button>
            <button @click="filtreStatut = 'en_attente'; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'en_attente' ? 'ring-2 ring-amber-500' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">En attente</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-amber-600">{{ stats.en_attente }}</p>
            </button>
            <button @click="filtreStatut = 'en_analyse'; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'en_analyse' ? 'ring-2 ring-blue-500' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">En analyse</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-blue-600">{{ stats.en_analyse }}</p>
            </button>
            <button @click="filtreStatut = 'acceptee'; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'acceptee' ? 'ring-2 ring-emerald-500' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Acceptées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.acceptee }}</p>
            </button>
            <button @click="filtreStatut = 'confirmee'; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'confirmee' ? 'ring-2 ring-emerald-700' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Confirmées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-700">{{ stats.confirmee }}</p>
            </button>
            <button @click="filtreStatut = 'refusee'; filtrer()"
                    :class="['rounded-xl bg-card p-4 text-left shadow-card transition hover:shadow-card-hover',
                        filtreStatut === 'refusee' ? 'ring-2 ring-red-500' : '']">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Refusées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-red-600">{{ stats.refusee }}</p>
            </button>
        </div>

        <!-- Tableau -->
        <div class="overflow-hidden rounded-xl bg-card shadow-card">

            <!-- Filtres -->
            <div class="border-b border-border-soft p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-sm font-bold text-text-main">Liste des dossiers</h2>

                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <select v-model="filtreEvenement" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                            <option value="">Tous les événements</option>
                            <option v-for="ev in evenements" :key="ev.id" :value="ev.id">
                                {{ ev.titre }}
                            </option>
                        </select>

                        <input v-model="recherche" @keyup.enter="filtrer"
                               type="search" placeholder="Nom, email, référence..."
                               class="w-56 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue"/>

                        <button v-if="filtreStatut || filtreEvenement || recherche"
                                @click="reinitialiser"
                                class="text-xs font-bold text-moov-orange hover:underline">
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <table class="min-w-full divide-y divide-border-soft text-sm">
                <thead class="bg-page-bg/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Référence</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Candidat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Événement</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Soumis le</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft bg-card">
                    <tr v-for="insc in inscriptions.data" :key="insc.id"
                        class="transition hover:bg-page-bg/50">
                        <!-- Référence -->
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs font-bold text-text-main">{{ insc.qr_code }}</p>
                            <p class="text-xs text-text-muted">#{{ insc.id }}</p>
                        </td>

                        <!-- Candidat -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                    {{ insc.user?.prenom?.[0] }}{{ insc.user?.nom?.[0] }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-text-main">
                                        {{ insc.user?.prenom }} {{ insc.user?.nom }}
                                    </p>
                                    <p class="truncate text-xs text-text-sub">{{ insc.user?.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Événement -->
                        <td class="px-4 py-3">
                            <p class="line-clamp-1 font-bold text-text-main">{{ insc.evenement?.titre }}</p>
                            <span :class="['mt-1 inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                couleurType(insc.evenement?.type?.code)]">
                                {{ insc.evenement?.type?.nom ?? '—' }}
                            </span>
                        </td>

                        <!-- Date -->
                        <td class="px-4 py-3 text-text-sub">
                            <p>{{ formaterDate(insc.created_at) }}</p>
                            <p class="text-xs text-text-muted">{{ formaterHeure(insc.created_at) }}</p>
                        </td>

                        <!-- Statut -->
                        <td class="px-4 py-3">
                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                couleurStatut(insc.statut).bg, couleurStatut(insc.statut).text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', couleurStatut(insc.statut).dot]"/>
                                {{ couleurStatut(insc.statut).label }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">

                                <!-- Voir détail (toujours dispo) -->
                                <Link :href="`/inscriptions/${insc.id}`"
                                      class="rounded p-1.5 text-text-sub transition hover:bg-moov-blue-50 hover:text-moov-blue"
                                      title="Voir détail">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </Link>

                                <!-- Actions selon le statut -->
                                <template v-if="insc.statut === 'en_attente'">
                                    <button @click="analyser(insc.id)"
                                            class="rounded p-1.5 text-text-sub transition hover:bg-blue-50 hover:text-blue-600"
                                            title="Marquer en analyse">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </button>
                                </template>

                                <template v-if="['en_attente', 'en_analyse'].includes(insc.statut)">
                                    <button @click="accepter(insc.id)"
                                            class="rounded p-1.5 text-text-sub transition hover:bg-emerald-50 hover:text-emerald-600"
                                            title="Accepter">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button @click="ouvrirModalRefus(insc)"
                                            class="rounded p-1.5 text-text-sub transition hover:bg-red-50 hover:text-red-600"
                                            title="Refuser">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Vide -->
            <div v-if="!inscriptions.data?.length" class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun dossier trouvé</p>
                <p class="mt-1 text-sm text-text-sub">
                    {{ filtreStatut || filtreEvenement || recherche
                        ? 'Aucun résultat avec ces filtres.'
                        : 'Les dossiers d\'inscription apparaîtront ici.' }}
                </p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="inscriptions.links?.length > 3" class="mt-6 flex justify-center gap-1">
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

        <!-- ════════════════════════ -->
        <!--   MODALE DE REFUS         -->
        <!-- ════════════════════════ -->
        <div v-if="inscriptionARefuser"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="fermerModalRefus">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Refuser ce dossier
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Candidat : <span class="font-bold">{{ inscriptionARefuser.user?.prenom }} {{ inscriptionARefuser.user?.nom }}</span>
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Motif du refus *
                        <span class="text-text-muted normal-case">(min. 10 caractères, sera communiqué au candidat)</span>
                    </label>
                    <textarea v-model="motifRefus" rows="4"
                              placeholder="Expliquez pourquoi le dossier n'est pas retenu..."
                              class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/10"/>
                    <p v-if="erreurMotif" class="mt-1 text-xs text-red-600">{{ erreurMotif }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="fermerModalRefus"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerRefus"
                            class="rounded-lg bg-red-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-red-700">
                        Confirmer le refus
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>