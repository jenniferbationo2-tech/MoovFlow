<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { $confirm } from '@/plugins/confirm'

const props = defineProps({
    evenement:              { type: Object, required: true },
    kpis:                   { type: Object, default: () => ({}) },
    certificats:            { type: Array, default: () => [] },
    presentsSansCertificat: { type: Array, default: () => [] },
})

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'

const formatDateLong = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    : '—'

const initiales = (user) =>
    `${user?.prenom?.[0] ?? ''}${user?.nom?.[0] ?? ''}`.toUpperCase() || 'U'

const genererTous = () => {
    if (await !$confirm(`Générer ${props.kpis.certificats_a_generer ?? 0} certificat(s) pour les participants présents ?`)) return
    router.post(`/evenements/${props.evenement.id}/certificats/generer`, {}, {
        preserveScroll: true,
    })
}

const envoyerEmail = (certificat) => {
    if (await !$confirm(`Envoyer le certificat à ${certificat.user.email} ?`)) return
    router.post(`/certificats/${certificat.id}/envoyer-email`, {}, {
        preserveScroll: true,
    })
}

const peutGenerer = computed(() => (props.kpis.certificats_a_generer ?? 0) > 0)
</script>

<template>
    <DashboardLayout>

        <Link :href="`/evenements/${evenement.id}`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
             Retour à l'événement
        </Link>

        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                    Post-événement  Certificats
                </p>
                <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                     Certificats de participation
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                </p>
            </div>

            <button v-if="peutGenerer" @click="genererTous"
                    class="inline-flex items-center gap-2 rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                Générer les certificats ({{ kpis.certificats_a_generer }})
            </button>
        </div>

        <!-- ─── KPIs ─── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Participants présents</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-moov-blue">{{ kpis.total_presents ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Certificats générés</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-emerald-600">{{ kpis.certificats_generes ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">À générer</p>
                <p class="mt-2 font-display text-3xl font-extrabold"
                   :class="(kpis.certificats_a_generer ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400'">
                    {{ kpis.certificats_a_generer ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Envoyés par email</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-purple-600">{{ kpis.certificats_envoyes ?? 0 }}</p>
            </div>
        </div>

        <!-- ─── BANDEAU INFO SI 0 PRESENT ─── -->
        <div v-if="kpis.total_presents === 0"
             class="mb-6 rounded-xl border-2 border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-bold text-amber-900">Aucun participant présent</p>
                    <p class="mt-0.5 text-sm text-amber-700">
                        Les certificats ne peuvent être générés que pour les participants ayant validé leur présence (QR code scanné le jour J).
                    </p>
                </div>
            </div>
        </div>

        <!-- ─── À GÉNÉRER ─── -->
        <div v-if="presentsSansCertificat.length > 0" class="mb-6">
            <h2 class="mb-3 flex items-center gap-2 font-display text-base font-extrabold text-text-main">
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-extrabold text-amber-700">
                    {{ presentsSansCertificat.length }}
                </span>
                À générer
            </h2>

            <div class="overflow-hidden rounded-xl bg-card shadow-card">
                <ul class="divide-y divide-border-soft">
                    <li v-for="present in presentsSansCertificat" :key="present.inscription_id"
                        class="flex items-center gap-3 p-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">
                            {{ initiales(present.user) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-text-main">{{ present.user.prenom }} {{ present.user.nom }}</p>
                            <p class="text-xs text-text-sub">{{ present.user.email }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                            En attente
                        </span>
                    </li>
                </ul>
            </div>

            <p class="mt-2 text-xs text-text-sub italic">
                Cliquez sur "Générer les certificats" en haut pour créer leurs PDF.
            </p>
        </div>

        <!-- ─── CERTIFICATS GÉNÉRÉS ─── -->
        <div v-if="certificats.length > 0">
            <h2 class="mb-3 flex items-center gap-2 font-display text-base font-extrabold text-text-main">
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-extrabold text-emerald-700">
                    {{ certificats.length }}
                </span>
                Certificats générés
            </h2>

            <div class="space-y-3">
                <div v-for="certif in certificats" :key="certif.id"
                     class="rounded-xl bg-card p-4 shadow-card transition hover:shadow-lg">

                    <div class="flex flex-wrap items-center justify-between gap-3">

                        <!-- Infos -->
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 text-sm font-bold">
                                {{ initiales(certif.user) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-display text-sm font-extrabold text-moov-blue">
                                        {{ certif.numero }}
                                    </p>
                                    <span v-if="certif.envoye_email"
                                          class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-700">
                                         Envoyé
                                    </span>
                                    <span v-else
                                          class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                         Non envoyé
                                    </span>
                                </div>
                                <p class="mt-0.5 font-bold text-text-main">
                                    {{ certif.user.prenom }} {{ certif.user.nom }}
                                </p>
                                <p class="text-xs text-text-sub">{{ certif.user.email }}</p>
                                <p class="mt-1 text-xs text-text-muted">
                                    Généré le {{ formatDate(certif.genere_at) }}
                                    <span v-if="certif.envoye_email"> · Envoyé le {{ formatDate(certif.envoye_at) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-2">
                            <a :href="`/certificats/${certif.id}/telecharger`"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-moov-blue/30 bg-moov-blue/10 px-3 py-1.5 text-xs font-bold text-moov-blue transition hover:bg-moov-blue hover:text-white">
                                 Télécharger
                            </a>
                            <button @click="envoyerEmail(certif)"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-purple-700">
                                ✉️ {{ certif.envoye_email ? 'Renvoyer' : 'Envoyer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── VIDE ─── -->
        <div v-else-if="presentsSansCertificat.length === 0 && kpis.total_presents > 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Tous les certificats sont générés !</h3>
        </div>

    </DashboardLayout>
</template>