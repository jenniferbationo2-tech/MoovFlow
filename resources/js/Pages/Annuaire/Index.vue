<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { $confirm } from '@/plugins/confirm'

const props = defineProps({
    participants: Object,
    stats:        Object,
    evenements:   Array,
    filters:      Object,
})


const recherche       = ref(props.filters?.search ?? '')
const filtreEvenement = ref(props.filters?.evenement_id ?? '')
const filtreStatut    = ref(props.filters?.statut ?? '')

const filtrer = () => {
    router.get('/annuaire', {
        search:       recherche.value || undefined,
        evenement_id: filtreEvenement.value || undefined,
        statut:       filtreStatut.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}

const reinitialiser = () => {
    recherche.value = ''
    filtreEvenement.value = ''
    filtreStatut.value = ''
    router.get('/annuaire')
}


const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const initiales = (u) => `${u.prenom?.[0] ?? ''}${u.nom?.[0] ?? ''}`.toUpperCase()

const couleurStatut = (statut) => ({
    en_attente:  'bg-amber-50 text-amber-700',
    en_analyse:  'bg-blue-50 text-blue-700',
    acceptee:    'bg-emerald-50 text-emerald-700',
    confirmee:   'bg-emerald-100 text-emerald-800',
    refusee:     'bg-red-50 text-red-700',
    annulee:     'bg-slate-100 text-slate-600',
    present:     'bg-emerald-200 text-emerald-900',
}[statut] || 'bg-slate-50 text-slate-600')

const labelStatut = (statut) => ({
    en_attente:  'En attente',
    en_analyse:  'En analyse',
    acceptee:    'Acceptée',
    confirmee:   'Confirmée',
    refusee:     'Refusée',
    annulee:     'Annulée',
    present:     'Présent',
}[statut] || statut)
</script>

<template>
    <DashboardLayout>

        <!-- ── HEADER ── -->
        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold text-text-main">
                Annuaire des Participants
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Vue d'ensemble des candidats inscrits aux événements
            </p>
        </div>

        <!-- ── KPIs ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Participants uniques</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                    {{ stats.total_participants }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Inscriptions totales</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-text-main">
                    {{ stats.total_inscriptions }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Confirmées</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">
                    {{ stats.total_confirmees }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Présents</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-700">
                    {{ stats.total_presents }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Taux présence</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-orange">
                    {{ stats.taux_presence }}<span class="text-base">%</span>
                </p>
            </div>
        </div>

        <!-- ── TABLEAU ── -->
        <div class="overflow-hidden rounded-xl bg-card shadow-card">

            <!-- Filtres -->
            <div class="border-b border-border-soft p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-sm font-bold text-text-main">Liste des participants</h2>

                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <select v-model="filtreEvenement" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                            <option value="">Tous les événements</option>
                            <option v-for="ev in evenements" :key="ev.id" :value="ev.id">
                                {{ ev.titre }}
                            </option>
                        </select>

                        <select v-model="filtreStatut" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold text-text-main outline-none focus:border-moov-blue">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="en_analyse">En analyse</option>
                            <option value="acceptee">Acceptées</option>
                            <option value="confirmee">Confirmées</option>
                            <option value="present">Présents</option>
                            <option value="refusee">Refusées</option>
                        </select>

                        <input v-model="recherche" @keyup.enter="filtrer"
                               type="search" placeholder="Nom, email, téléphone..."
                               class="w-56 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue"/>

                        <button v-if="recherche || filtreEvenement || filtreStatut"
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
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                            Participant
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                            Contact
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">
                            Inscriptions
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">
                            Confirmées
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">
                            Présent(s)
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">
                            Dernière inscription
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft bg-card">
                    <tr v-for="p in participants.data" :key="p.id"
                        class="transition hover:bg-page-bg/50">

                        <!-- Participant -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                    {{ initiales(p) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-text-main">
                                        {{ p.prenom }} {{ p.nom }}
                                    </p>
                                    <p class="text-xs text-text-muted">
                                        Membre depuis {{ formaterDate(p.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Contact -->
                        <td class="px-4 py-3">
                            <p class="text-text-main">{{ p.email }}</p>
                            <p v-if="p.telephone" class="text-xs text-text-sub">{{ p.telephone }}</p>
                        </td>

                        <!-- Inscriptions totales -->
                        <td class="px-4 py-3 text-center">
                            <span class="font-display text-lg font-extrabold text-moov-blue">
                                {{ p.inscriptions_count }}
                            </span>
                        </td>

                        <!-- Confirmées -->
                        <td class="px-4 py-3 text-center">
                            <span class="font-display text-lg font-extrabold text-emerald-600">
                                {{ p.confirmees_count }}
                            </span>
                        </td>

                        <!-- Présents -->
                        <td class="px-4 py-3 text-center">
                            <span class="font-display text-lg font-extrabold text-emerald-700">
                                {{ p.presents_count }}
                            </span>
                        </td>

                        <!-- Dernière inscription -->
                        <td class="px-4 py-3">
                            <div v-if="p.derniere_inscription">
                                <p class="line-clamp-1 text-xs font-bold text-text-main">
                                    {{ p.derniere_inscription.evenement?.titre }}
                                </p>
                                <span :class="['mt-1 inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                    couleurStatut(p.derniere_inscription.statut)]">
                                    {{ labelStatut(p.derniere_inscription.statut) }}
                                </span>
                            </div>
                            <span v-else class="text-xs text-text-muted">—</span>
                        </td>

                        <!-- Action -->
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/annuaire/${p.id}`"
                                  class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-xs font-bold text-text-main transition hover:border-moov-blue hover:text-moov-blue">
                                Voir profil →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Vide -->
            <div v-if="!participants.data?.length" class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun participant trouvé</p>
                <p class="mt-1 text-sm text-text-sub">
                    {{ recherche || filtreEvenement || filtreStatut
                        ? 'Aucun résultat avec ces filtres.'
                        : 'Les participants apparaîtront ici après leurs premières inscriptions.' }}
                </p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="participants.links?.length > 3" class="mt-6 flex justify-center gap-1">
            <template v-for="link in participants.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" v-html="link.label"
                      :class="['rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                          link.active
                              ? 'bg-moov-blue text-white'
                              : 'border border-border-soft bg-white text-text-sub hover:border-moov-blue/30']"/>
                <span v-else v-html="link.label"
                      class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-sm text-text-muted"/>
            </template>
        </div>

    </DashboardLayout>
</template>