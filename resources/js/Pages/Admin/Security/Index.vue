<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    kpis:                { type: Object, default: () => ({}) },
    comptesBloques:      { type: Array, default: () => [] },
    tentativesEchouees:  { type: Array, default: () => [] },
    activiteParUser:     { type: Array, default: () => [] },
    alertes:             { type: Object, default: () => ({}) },
})

// ─── ONGLETS ────────────────────────────
const ongletActif = ref('bloques')

const onglets = computed(() => [
    { key: 'bloques',     label: 'Comptes bloqués',     count: props.comptesBloques?.length ?? 0,     icon: 'lock' },
    { key: 'tentatives',  label: 'Tentatives échouées', count: props.tentativesEchouees?.length ?? 0, icon: 'alert' },
    { key: 'activite',    label: 'Activité par user',   count: props.activiteParUser?.length ?? 0,    icon: 'users' },
])

// ─── HELPERS ────────────────────────────
const formatDate = (d) => d
    ? new Date(d).toLocaleString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
    : '—'

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

const couleurRisque = (risque) => ({
    faible:   { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Faible' },
    modere:   { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   label: 'Modéré' },
    eleve:    { bg: 'bg-orange-50',  text: 'text-orange-700',  dot: 'bg-orange-500',  label: 'Élevé' },
    critique: { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     label: 'Critique' },
}[risque] || { bg: 'bg-slate-50', text: 'text-slate-700', dot: 'bg-slate-400', label: '—' })

const initiales = (u) =>
    `${u?.prenom?.[0] ?? ''}${u?.nom?.[0] ?? ''}`.toUpperCase() || 'U'

// ─── ACTIONS ────────────────────────────
const debloquer = (user) => {
    if (!confirm(`Débloquer le compte de ${user.prenom} ${user.nom} ?`)) return
    router.post(`/admin/security/${user.id}/debloquer`, {}, {
        preserveScroll: true,
    })
}

const aDesAlertes = computed(() =>
    (props.alertes?.ips_suspectes?.length ?? 0) > 0 ||
    (props.alertes?.comptes_cibles?.length ?? 0) > 0
)
</script>

<template>
    <DashboardLayout>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Administration
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Centre de Sécurité
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Surveillance des comptes et détection des tentatives suspectes
            </p>
        </div>

        <!-- ─── BANDEAU ALERTES (si activité suspecte) ─── -->
        <div v-if="aDesAlertes" class="mb-6 rounded-xl border-2 border-red-200 bg-red-50 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-red-900">
                        Activité suspecte détectée (dernière heure)
                    </p>

                    <div v-if="alertes.ips_suspectes?.length" class="mt-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                            IPs suspectes ({{ alertes.ips_suspectes.length }})
                        </p>
                        <div class="mt-1 space-y-1">
                            <p v-for="ip in alertes.ips_suspectes" :key="ip.ip" class="text-sm text-red-800">
                                <span class="font-mono font-bold">{{ ip.ip }}</span>
                                : <strong>{{ ip.tentatives }}</strong> tentatives échouées
                                <span v-if="ip.emails_tentes?.length"> sur {{ ip.emails_tentes.length }} compte(s)</span>
                            </p>
                        </div>
                    </div>

                    <div v-if="alertes.comptes_cibles?.length" class="mt-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                            Comptes ciblés ({{ alertes.comptes_cibles.length }})
                        </p>
                        <div class="mt-1 space-y-1">
                            <p v-for="cible in alertes.comptes_cibles" :key="cible.email" class="text-sm text-red-800">
                                <span class="font-mono font-bold">{{ cible.email }}</span>
                                : <strong>{{ cible.tentatives }}</strong> tentatives
                                <span v-if="cible.ips?.length"> depuis {{ cible.ips.length }} IP(s)</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── KPIs ─── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Comptes bloqués</p>
                    <div class="rounded-lg bg-red-50 p-2">
                        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-red-600">{{ kpis.comptes_bloques ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">Actuellement</p>
            </div>

            <div class="rounded-xl bg-card p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Échecs aujourd'hui</p>
                    <div class="rounded-lg bg-amber-50 p-2">
                        <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-amber-600">{{ kpis.echecs_aujourdhui ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">Connexions échouées</p>
            </div>

            <div class="rounded-xl bg-card p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">IPs suspectes</p>
                    <div class="rounded-lg bg-orange-50 p-2">
                        <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-orange-600">{{ kpis.ips_suspectes ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">≥3 échecs en 1h</p>
            </div>

            <div class="rounded-xl bg-card p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Comptes désactivés</p>
                    <div class="rounded-lg bg-slate-100 p-2">
                        <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 font-display text-3xl font-extrabold text-slate-600">{{ kpis.comptes_desactives ?? 0 }}</p>
                <p class="mt-1 text-xs text-text-sub">Manuellement</p>
            </div>
        </div>

        <!-- ─── ONGLETS ─── -->
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

            <!-- ═════ ONGLET COMPTES BLOQUÉS ═════ -->
            <div v-if="ongletActif === 'bloques'" class="p-5">
                <div v-if="comptesBloques.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-3 font-bold text-text-main">Aucun compte bloqué</p>
                    <p class="mt-1 text-sm text-text-sub">Tous les comptes sont opérationnels</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="u in comptesBloques" :key="u.id"
                         class="rounded-xl border-2 border-red-200 bg-red-50/30 p-4">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 text-sm font-bold">
                                    {{ initiales(u) }}
                                </div>
                                <div>
                                    <Link :href="`/admin/users/${u.id}`"
                                          class="font-bold text-text-main hover:text-moov-blue">
                                        {{ u.prenom }} {{ u.nom }}
                                    </Link>
                                    <p class="text-xs text-text-sub">{{ u.email }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span v-for="r in u.roles" :key="r"
                                              :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider', couleurRole(r)]">
                                            {{ labelRole(r) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-bold uppercase tracking-wider text-red-700">
                                    Bloqué {{ u.bloque_jusqu_a_human }}
                                </p>
                                <p class="text-xs text-text-sub">
                                    {{ u.tentatives_connexion }} tentative(s)
                                </p>
                                <button @click="debloquer(u)"
                                        class="mt-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-emerald-700">
                                    Débloquer
                                </button>
                            </div>
                        </div>

                        <div v-if="u.derniere_tentative"
                             class="mt-3 border-t border-red-200 pt-2 text-xs text-text-sub">
                            Dernière tentative : <span class="font-bold">{{ u.derniere_tentative.date }}</span>
                            depuis <span class="font-mono">{{ u.derniere_tentative.ip }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════ ONGLET TENTATIVES ÉCHOUÉES ═════ -->
            <div v-else-if="ongletActif === 'tentatives'" class="p-5">
                <div v-if="tentativesEchouees.length === 0" class="py-12 text-center">
                    <p class="font-bold text-text-main">Aucune tentative échouée</p>
                    <p class="mt-1 text-sm text-text-sub">Sur les 7 derniers jours</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-soft text-sm">
                        <thead class="bg-page-bg/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Email tenté</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut HTTP</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft">
                            <tr v-for="t in tentativesEchouees" :key="t.id"
                                class="transition hover:bg-page-bg/50">
                                <td class="px-4 py-3">
                                    <p class="text-text-main">{{ formatDate(t.created_at) }}</p>
                                    <p class="text-xs text-text-muted">{{ t.created_at_human }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-text-main">{{ t.email_tente ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-text-sub">{{ t.ip }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">
                                        {{ t.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link v-if="t.causer"
                                          :href="`/admin/users/${t.causer.id}`"
                                          class="text-sm font-bold text-moov-blue hover:underline">
                                        {{ t.causer.prenom }} {{ t.causer.nom }}
                                    </Link>
                                    <span v-else class="text-xs text-text-muted italic">Anonyme</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═════ ONGLET ACTIVITÉ PAR USER ═════ -->
            <div v-else-if="ongletActif === 'activite'" class="p-5">
                <div v-if="activiteParUser.length === 0" class="py-12 text-center">
                    <p class="font-bold text-text-main">Aucune donnée</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-soft text-sm">
                        <thead class="bg-page-bg/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Rôle</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Connexions</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Échecs (7j)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Dernière connexion</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Risque</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-soft">
                            <tr v-for="u in activiteParUser" :key="u.id"
                                class="transition hover:bg-page-bg/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                            {{ initiales(u) }}
                                        </div>
                                        <div>
                                            <Link :href="`/admin/users/${u.id}`"
                                                  class="font-bold text-text-main hover:text-moov-blue">
                                                {{ u.prenom }} {{ u.nom }}
                                            </Link>
                                            <p class="text-xs text-text-muted">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-for="r in u.roles" :key="r"
                                          :class="['inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider', couleurRole(r)]">
                                        {{ labelRole(r) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-text-main">{{ u.total_connexions }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span :class="['font-bold',
                                        u.echecs_recents === 0 ? 'text-text-muted' :
                                        u.echecs_recents <= 2 ? 'text-amber-600' : 'text-red-600']">
                                        {{ u.echecs_recents }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-text-sub">
                                    {{ u.derniere_connexion_human ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                        couleurRisque(u.risque).bg, couleurRisque(u.risque).text]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', couleurRisque(u.risque).dot]"/>
                                        {{ couleurRisque(u.risque).label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>