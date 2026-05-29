<script setup>
import { Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    enquetes: { type: Array, default: () => [] },
})

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    : '—'
</script>

<template>
    <DashboardLayout>

        
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Mon espace
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Mes enquêtes
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Donnez votre avis sur les événements auxquels vous avez participé
            </p>
        </div>

        <!-- ─── VIDE ─── -->
        <div v-if="enquetes.length === 0"
             class="rounded-xl bg-card p-12 text-center shadow-card">
            <svg class="mx-auto h-16 w-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-4 font-display text-lg font-extrabold text-text-main">Aucune enquête en attente</h3>
            <p class="mt-1 text-sm text-text-sub">
                Vous serez notifié quand des enquêtes seront disponibles.
            </p>
        </div>

        <!-- ─── LISTE ─── -->
        <div v-else class="space-y-3">
            <div v-for="enquete in enquetes" :key="enquete.id"
                 class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-lg">

                <div class="flex flex-wrap items-start justify-between gap-4">

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Événement : {{ enquete.evenement.titre }}
                        </p>
                        <h3 class="mt-1 font-display text-lg font-extrabold text-text-main">
                            {{ enquete.titre }}
                        </h3>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                            <span><strong>{{ enquete.nb_questions }}</strong> question(s)</span>
                            <span>·</span>
                            <span>Événement du {{ formatDate(enquete.evenement.date_debut) }}</span>
                        </div>
                    </div>

                    <div>
                        <div v-if="enquete.a_deja_repondu"
                             class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                             Déjà répondu
                        </div>
                        <Link v-else
                              :href="`/mes-enquetes/${enquete.id}`"
                              class="rounded-lg bg-moov-blue px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-blue-700">
                            Répondre 
                        </Link>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>