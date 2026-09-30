<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    types: { type: Array, required: true },
})

const emit = defineEmits(['selectionner'])

// Description courte par type
const descriptions = {
    BARA_MOUSSO: 'Concours valorisant les femmes entrepreneures',
    CONF:        'Conférence, table ronde ou symposium d\'experts',
    SPORT:       'Tournoi avec équipes et phases éliminatoires',
    CHALLENGE:   'Concours d\'innovation avec sélection de projets',
    FORMATION:   'Formation avec modules et certifications',
    HACK:        'Hackathon collaboratif de développement',
    SALON:       'Présence Moov sur un événement externe',
}

const getDescription = (code) => descriptions[code] || 'Événement standard'

const choisir = (type) => {
    emit('selectionner', type)
}
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">

            <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-xl">

                <!-- ── EN-TÊTE ── -->
                <div class="border-b border-slate-200 px-8 py-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Étape 1 sur 5
                    </p>
                    <h2 class="mt-1 font-display text-2xl font-extrabold text-slate-900">
                        Type d'événement
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Sélectionnez la catégorie de votre événement
                    </p>
                </div>

                <!-- ── LISTE DES TYPES ── -->
                <div class="divide-y divide-slate-100">

                    <button v-for="type in types" :key="type.id" @click="choisir(type)"
                        class="group flex w-full items-center justify-between gap-4 px-8 py-5 text-left transition hover:bg-slate-50">

                        <div class="min-w-0 flex-1">
                            <h3 class="font-display text-base font-bold text-slate-900 group-hover:text-moov-blue">
                                {{ type.nom }}
                            </h3>
                            <p class="mt-0.5 text-sm text-slate-600">
                                {{ getDescription(type.code) }}
                            </p>
                        </div>

                        <span class="flex-shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-moov-blue">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </button>
                </div>

                <!-- ── FOOTER ── -->
                <div class="border-t border-slate-200 bg-slate-50 px-8 py-3 rounded-b-2xl">
                    <Link href="/evenements" class="text-xs font-bold text-slate-600 transition hover:text-moov-blue">
                        ← Annuler et retourner à la liste
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>