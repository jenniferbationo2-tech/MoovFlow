<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    user:             { type: Object, required: true },
    activites:        { type: Array, default: () => [] },
    connexions:       { type: Array, default: () => [] },
    evenementsCrees:  { type: Array, default: () => [] },
    kpis:             { type: Object, default: () => ({}) },
})

const ongletActif = ref('activites')

// ─── COMPUTED SAFE ─── (avec fallback systématique)
const initiales = computed(() => {
    const p = props.user?.prenom?.[0] ?? ''
    const n = props.user?.nom?.[0] ?? ''
    return (p + n).toUpperCase() || 'U'
})

const onglets = computed(() => [
    { key: 'activites',  label: 'Activités',         count: props.activites?.length ?? 0 },
    { key: 'connexions', label: 'Connexions',        count: props.connexions?.length ?? 0 },
    { key: 'evenements', label: 'Événements créés',  count: props.evenementsCrees?.length ?? 0 },
])

// ─── HELPERS ───
const labelRole = (role) => ({
    admin:             'Administrateur',
    responsable_dcirp: 'Responsable dCIRP',
    organisateur:      'Organisateur',
    participant:       'Participant',
}[role] || role)

const couleurRole = (role) => ({
    admin:             'bg-red-50 text-red-700',
    responsable_dcirp: 'bg-purple-50 text-purple-700',
    organisateur:      'bg-orange-50 text-orange-700',
    participant:       'bg-emerald-50 text-emerald-700',
}[role] || 'bg-slate-50 text-slate-700')

const statutUser = computed(() => {
    if (props.user?.deleted_at) return { label: 'Archivé', bg: 'bg-slate-200', text: 'text-slate-700', dot: 'bg-slate-500' }
    if (!props.user?.is_active) return { label: 'Désactivé', bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400' }
    if (props.user?.bloque_jusqu_a && new Date(props.user.bloque_jusqu_a) > new Date()) {
        return { label: 'Bloqué', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' }
    }
    return { label: 'Actif', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' }
})

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) : '—'
const formatDateTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

// ─── ACTIONS ───
const archiver = () => {
    if (!confirm(`Archiver le compte de ${props.user.prenom} ${props.user.nom} ?`)) return
    if (!confirm('Confirmer définitivement ?')) return
    router.delete(`/admin/users/${props.user.id}`)
}

const restaurer = () => {
    if (confirm('Restaurer ce compte ?')) router.post(`/admin/users/${props.user.id}/restore`)
}

const toggleActif = () => {
    const action = props.user.is_active ? 'désactiver' : 'activer'
    if (confirm(`Voulez-vous ${action} ce compte ?`)) {
        router.patch(`/admin/users/${props.user.id}/toggle-active`)
    }
}
</script>

<template>
    <DashboardLayout>

        <Link href="/admin/users" class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← Retour à la liste
        </Link>

        <!-- Carte utilisateur -->
        <div class="mb-6 overflow-hidden rounded-xl bg-card shadow-card">
            <div class="bg-gradient-to-r from-moov-blue to-blue-700 p-6 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/80">Profil utilisateur</p>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap items-start gap-5">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-moov-blue text-2xl font-extrabold text-white shadow-lg">
                        {{ initiales }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="font-display text-2xl font-extrabold text-text-main">
                            {{ user.prenom }} {{ user.nom }}
                        </h1>
                        <p class="mt-1 text-sm text-text-sub">{{ user.email }}</p>
                        <p v-if="user.telephone" class="text-sm text-text-sub">{{ user.telephone }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span v-for="role in (user.roles || [])" :key="role"
                                  :class="['inline-block rounded px-2.5 py-1 text-xs font-bold uppercase tracking-wider', couleurRole(role)]">
                                {{ labelRole(role) }}
                            </span>
                            <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                statutUser.bg, statutUser.text]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statutUser.dot]"/>
                                {{ statutUser.label }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-text-muted">
                            Membre depuis le {{ formatDate(user.created_at) }}
                            <span v-if="kpis.jours_anciennete > 0"> · {{ kpis.jours_anciennete }} jour(s)</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button v-if="user.deleted_at" @click="restaurer"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                            Restaurer
                        </button>
                        <template v-else>
                            <button @click="toggleActif"
                                    :class="['rounded-lg px-4 py-2 text-sm font-bold text-white',
                                        user.is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700']">
                                {{ user.is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                            <button @click="archiver"
                                    class="rounded-lg border-2 border-red-300 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">
                                Archiver
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Activités totales</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ kpis.total_activites ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Connexions</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ kpis.total_connexions ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Échecs connexion</p>
                <p class="mt-2 font-display text-3xl font-extrabold"
                   :class="(kpis.connexions_echec ?? 0) > 0 ? 'text-red-600' : 'text-slate-400'">
                    {{ kpis.connexions_echec ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Événements créés</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-orange-600">{{ kpis.evenements_crees ?? 0 }}</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="overflow-hidden rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft">
                <nav class="flex gap-1 px-4">
                    <button v-for="o in onglets" :key="o.key"
                            @click="ongletActif = o.key"
                            :class="['flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition',
                                ongletActif === o.key
                                    ? 'border-moov-blue text-moov-blue'
                                    : 'border-transparent text-text-sub hover:text-text-main']">
                        {{ o.label }}
                        <span :class="['rounded-full px-2 py-0.5 text-[10px] font-extrabold',
                            ongletActif === o.key ? 'bg-moov-blue text-white' : 'bg-page-bg text-text-muted']">
                            {{ o.count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Activités -->
            <div v-if="ongletActif === 'activites'" class="p-5">
                <p v-if="!activites || activites.length === 0" class="py-12 text-center text-sm text-text-sub">
                    Aucune activité
                </p>
                <div v-else class="space-y-2">
                    <div v-for="a in activites" :key="a.id"
                         class="rounded-lg border border-border-soft bg-white p-3">
                        <p class="text-sm font-medium text-text-main">{{ a.description }}</p>
                        <p class="mt-1 text-xs text-text-muted">{{ a.created_at_human }}</p>
                    </div>
                </div>
            </div>

            <!-- Connexions -->
            <div v-else-if="ongletActif === 'connexions'" class="p-5">
                <p v-if="!connexions || connexions.length === 0" class="py-12 text-center text-sm text-text-sub">
                    Aucune connexion
                </p>
                <table v-else class="min-w-full divide-y divide-border-soft text-sm">
                    <thead class="bg-page-bg/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-bold uppercase tracking-wider text-text-sub">IP</th>
                            <th class="px-4 py-2 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-soft">
                        <tr v-for="c in connexions" :key="c.id">
                            <td class="px-4 py-2 text-text-main">{{ formatDateTime(c.created_at) }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-text-sub">{{ c.ip }}</td>
                            <td class="px-4 py-2">
                                <span v-if="c.reussie" class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                    Réussie ({{ c.status }})
                                </span>
                                <span v-else class="rounded-full bg-red-50 px-2 py-1 text-xs font-bold text-red-700">
                                    Échec ({{ c.status }})
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Événements -->
            <div v-else-if="ongletActif === 'evenements'" class="p-5">
                <p v-if="!evenementsCrees || evenementsCrees.length === 0" class="py-12 text-center text-sm text-text-sub">
                    Aucun événement créé
                </p>
                <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <Link v-for="e in evenementsCrees" :key="e.id"
                          :href="`/evenements/${e.id}`"
                          class="rounded-xl border border-border-soft bg-white p-4 hover:shadow-card">
                        <h3 class="font-bold text-text-main">{{ e.titre }}</h3>
                        <p class="mt-1 text-xs text-text-sub">{{ e.type_nom }} · {{ formatDate(e.date_debut) }}</p>
                        <p class="mt-2 text-xs font-bold text-moov-blue">
                            {{ e.inscriptions_count }} inscription(s)
                        </p>
                    </Link>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>