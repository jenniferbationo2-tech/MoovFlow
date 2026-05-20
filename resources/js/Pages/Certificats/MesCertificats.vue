<script setup>
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    certificats: { type: Array, default: () => [] },
})

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    : '—'
</script>

<template>
    <DashboardLayout>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Mon espace
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                🎓 Mes Certificats
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Téléchargez vos certificats de participation officiels
            </p>
        </div>

        <!-- ─── VIDE ─── -->
        <div v-if="certificats.length === 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Aucun certificat pour le moment</h3>
            <p class="mt-2 text-sm text-text-sub">
                Vous recevrez vos certificats après avoir participé à un événement<br>
                et que l'organisateur les aura générés.
            </p>
            <Link href="/evenements"
                  class="mt-4 inline-block rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-blue-700">
                Voir les événements
            </Link>
        </div>

        <!-- ─── LISTE DES CERTIFICATS ─── -->
        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <div v-for="certif in certificats" :key="certif.id"
                 class="overflow-hidden rounded-xl bg-card shadow-card transition hover:shadow-xl">

                <!-- Header coloré -->
                <div class="bg-gradient-to-br from-moov-blue to-blue-700 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 text-2xl backdrop-blur">
                            🎓
                        </div>
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold backdrop-blur">
                            {{ certif.numero }}
                        </span>
                    </div>
                </div>

                <!-- Corps -->
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Événement
                    </p>
                    <h3 class="mt-1 font-display text-lg font-extrabold text-text-main line-clamp-2">
                        {{ certif.evenement.titre }}
                    </h3>

                    <div class="mt-3 space-y-1 text-xs text-text-sub">
                        <p class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Événement du {{ formatDate(certif.evenement.date_debut) }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Délivré le {{ formatDate(certif.genere_at) }}</span>
                        </p>
                    </div>

                    <!-- Bouton -->
                    <a :href="`/mes-certificats/${certif.id}/telecharger`"
                       class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-moov-blue px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Télécharger mon certificat
                    </a>
                </div>
            </div>
        </div>

        <!-- ─── INFO BOX ─── -->
        <div v-if="certificats.length > 0"
             class="mt-6 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
            <p class="text-xs text-blue-700">
                <strong>Conservez précieusement vos certificats</strong> — ils peuvent vous être demandés
                comme justificatif de participation pour des candidatures professionnelles ou académiques.
            </p>
        </div>

    </DashboardLayout>
</template>