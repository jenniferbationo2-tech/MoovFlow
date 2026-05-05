<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    stats:               Object,
    derniers_evenements: Array,
    derniers_users:      Array,
})

const page  = usePage()
const user  = computed(() => page.props.auth?.user)
const roles = computed(() => user.value?.roles ?? [])

const titrePage = computed(() => {
    if (roles.value.includes('admin'))             return 'Pilotage Stratégique RSE'
    if (roles.value.includes('responsable_dcirp')) return 'Supervision dCIRP'
    if (roles.value.includes('organisateur'))      return 'Mes Événements'
    if (roles.value.includes('participant'))       return 'Mon Espace'
    return 'Tableau de Bord'
})

const sousTitre = computed(() => {
    if (roles.value.includes('admin'))             return 'Vue d\'ensemble des actions et de l\'engagement communautaire'
    if (roles.value.includes('responsable_dcirp')) return 'Validation et suivi des événements RSE'
    if (roles.value.includes('organisateur'))      return 'Planification et gestion de vos événements'
    return 'Bienvenue sur votre tableau de bord'
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const formatNombre = (n) => {
    if (!n) return '0'
    if (n >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '') + 'M'
    if (n >= 1000)    return (n / 1000).toFixed(1).replace('.0', '') + 'K'
    return n.toString()
}
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE PAGE ── -->
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-4xl font-extrabold text-text-main">
                    {{ titrePage }}
                </h1>
                <p class="mt-1 text-sm text-text-sub">{{ sousTitre }}</p>
            </div>
            <a v-if="roles.includes('organisateur') || roles.includes('admin') || roles.includes('responsable_dcirp')"
               
            href="/evenements/create"
               class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                Nouvel Événement +
            </a>
        </div>

        <!-- ── KPIs (style modèle pro) ── -->
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

            <!-- KPI 1 -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Bénéficiaires Directs
                </p>
                <p class="mt-3 font-display text-4xl font-extrabold text-moov-blue">
                    {{ formatNombre(stats?.beneficiaires_directs ?? 0) }}
                </p>
                <p class="mt-2 text-xs text-emerald-600">
                    ↑ {{ stats?.croissance_beneficiaires ?? 0 }}% vs précédent
                </p>
            </div>

            <!-- KPI 2 -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Femmes Bénéficiaires
                </p>
                <p class="mt-3 font-display text-4xl font-extrabold text-moov-blue">
                    {{ stats?.taux_femmes ?? 0 }}<span class="text-2xl">%</span>
                </p>
                <p class="mt-2 text-xs text-text-sub">
                    Cible : {{ stats?.cible_femmes ?? 60 }}%
                </p>
            </div>

            <!-- KPI 3 -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Associations Soutenues
                </p>
                <p class="mt-3 font-display text-4xl font-extrabold text-moov-blue">
                    {{ stats?.associations_soutenues ?? 0 }}
                </p>
                <p class="mt-2 text-xs text-text-sub">
                    Projets validés
                </p>
            </div>

            <!-- KPI 4 -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Budget Engagé
                </p>
                <p class="mt-3 font-display text-4xl font-extrabold text-moov-blue">
                    {{ formatNombre(stats?.budget_engage ?? 0) }}
                    <span class="text-xl">F</span>
                </p>
                <p class="mt-2 text-xs text-moov-orange">
                    {{ stats?.taux_budget ?? 0 }}% de l'enveloppe
                </p>
            </div>
        </div>

        <!-- ── 2 COLONNES : Évolution + Événements récents ── -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Évolution (placeholder graphique) -->
            <div class="lg:col-span-2 rounded-xl bg-card p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-text-main">
                    Évolution de l'Impact Social
                </h3>
                <p class="mt-1 text-xs text-text-sub">12 derniers mois</p>

                <!-- Graphique simulé avec barres -->
                <div class="mt-6 flex items-end justify-between gap-2 h-48">
                    <div v-for="(mois, i) in ['Jan','Feb','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc']"
                         :key="mois"
                         class="flex flex-1 flex-col items-center gap-2">
                        <div class="w-full rounded-t bg-moov-blue transition hover:bg-moov-blue-light"
                             :style="`height: ${30 + (i * 5) + Math.sin(i) * 20}%`"/>
                        <span class="text-[10px] text-text-sub">{{ mois }}</span>
                    </div>
                </div>
            </div>

            <!-- Événements récents -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-display text-base font-bold text-text-main">
                        Événements récents
                    </h3>
                    <a href="/evenements"
                       class="text-xs font-bold text-moov-orange hover:underline">
                        Voir tout →
                    </a>
                </div>

                <div v-if="derniers_evenements?.length" class="space-y-3">
                    <a v-for="ev in derniers_evenements" :key="ev.id"
                       :href="`/evenements/${ev.id}`"
                       class="flex items-start justify-between gap-3 rounded-lg border border-border-soft p-3 transition hover:border-moov-blue/30 hover:shadow-sm">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-text-main">{{ ev.titre }}</p>
                            <p class="mt-0.5 text-xs text-text-sub">
                                {{ ev.lieu?.nom ?? '—' }} · {{ formaterDate(ev.date_debut) }}
                            </p>
                        </div>
                        <span :class="[
                            'flex-shrink-0 rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                            ev.type_evenement?.code === 'BARA_MOUSSO' ? 'bg-moov-orange-50 text-moov-orange-dark' :
                            ev.type_evenement?.code === 'CONF'        ? 'bg-pink-50 text-pink-700' :
                            ev.type_evenement?.code === 'SPORT'       ? 'bg-blue-50 text-blue-700' :
                            ev.type_evenement?.code === 'FORMATION'   ? 'bg-emerald-50 text-emerald-700' :
                            ev.type_evenement?.code === 'HACK'        ? 'bg-amber-50 text-amber-700' :
                            ev.type_evenement?.code === 'SALON'       ? 'bg-indigo-50 text-indigo-700' :
                            'bg-slate-100 text-slate-700'
                        ]">
                            {{ ev.type_evenement?.nom ?? 'Type' }}
                        </span>
                    </a>
                </div>

                <p v-else class="rounded-lg border border-dashed border-border-soft py-8 text-center text-sm text-text-sub">
                    Aucun événement à afficher
                </p>
            </div>
        </div>

    </DashboardLayout>
</template>