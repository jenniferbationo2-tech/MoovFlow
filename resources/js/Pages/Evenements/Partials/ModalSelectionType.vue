<script setup>
const props = defineProps({
    types: { type: Array, required: true },
})

const emit = defineEmits(['selectionner'])

// Configuration visuelle de chaque type
const configType = {
    BARA_MOUSSO: {
        couleur: 'from-pink-500 to-rose-600',
        couleurBg: 'bg-pink-50',
        couleurTxt: 'text-pink-700',
        description: 'Concours valorisant les femmes entrepreneures du Burkina',
        // Icône : Trophée (award)
        svgPath: 'M5 3a2 2 0 00-2 2v1a4 4 0 004 4h.5M19 3a2 2 0 012 2v1a4 4 0 01-4 4h-.5M9 21h6M12 17v4M7 3h10v6a5 5 0 11-10 0V3z',
    },
    CONF: {
        couleur: 'from-rose-500 to-rose-700',
        couleurBg: 'bg-rose-50',
        couleurTxt: 'text-rose-700',
        description: 'Conférence professionnelle ouverte au public ciblé',
        // Icône : Présentateur (microphone)
        svgPath: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
    },
    SPORT: {
        couleur: 'from-blue-500 to-blue-700',
        couleurBg: 'bg-blue-50',
        couleurTxt: 'text-blue-700',
        description: 'Tournoi avec équipes, phases et classement automatique',
        // Icône : Trophée sportif
        svgPath: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    },
    CHALLENGE: {
        couleur: 'from-violet-500 to-violet-700',
        couleurBg: 'bg-violet-50',
        couleurTxt: 'text-violet-700',
        description: 'Challenge d\'innovation avec sélection de projets',
        // Icône : Ampoule / Idée
        svgPath: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    },
    FORMATION: {
        couleur: 'from-emerald-500 to-emerald-700',
        couleurBg: 'bg-emerald-50',
        couleurTxt: 'text-emerald-700',
        description: 'Formation numérique avec niveaux et certifications',
        // Icône : Académique (chapeau)
        svgPath: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222',
    },
    HACK: {
        couleur: 'from-orange-500 to-orange-700',
        couleurBg: 'bg-orange-50',
        couleurTxt: 'text-orange-700',
        description: 'Hackathon collaboratif sur 24h à 72h',
        // Icône : Code / Terminal
        svgPath: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    },
    SALON: {
        couleur: 'from-indigo-500 to-indigo-700',
        couleurBg: 'bg-indigo-50',
        couleurTxt: 'text-indigo-700',
        description: 'Présence Moov sur un événement externe (vitrine)',
        // Icône : Bâtiment
        svgPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
}

const getConfig = (code) => configType[code] || {
    couleur: 'from-slate-500 to-slate-700',
    couleurBg: 'bg-slate-50',
    couleurTxt: 'text-slate-700',
    description: 'Événement standard',
    svgPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
}


const choisir = (type) => {
    emit('selectionner', type)
}
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">

            <div class="relative w-full max-w-5xl rounded-3xl bg-white shadow-2xl">

                <!-- ── EN-TÊTE ── -->
                <div class="border-b border-border-soft px-6 py-5 sm:px-8 sm:py-6">
                    <div class="text-center">
                        <span
                            class="inline-block rounded-full bg-moov-orange/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-moov-orange">
                            Étape 1 / 5
                        </span>
                        <h2 class="mt-3 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                            Quel type d'événement créer ?
                        </h2>
                        <p class="mt-2 text-sm text-text-sub">
                            Choisissez la catégorie de votre événement pour personnaliser le formulaire
                        </p>
                    </div>
                </div>

                <!-- ── GRILLE DES 7 TYPES ── -->
                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        <button v-for="type in types" :key="type.id" @click="choisir(type)"
                            class="group relative overflow-hidden rounded-2xl border-2 border-border-soft bg-white p-5 text-left transition hover:border-moov-orange hover:shadow-lg hover:-translate-y-1">

                            <!-- Bandeau coloré -->
                            <div
                                :class="['absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r', getConfig(type.code).couleur]" />

                            <!-- Icône SVG + Nom -->
                            <div class="flex items-start gap-3">
                                <div :class="['flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl',
                                    getConfig(type.code).couleurBg]">
                                    <svg :class="['h-6 w-6', getConfig(type.code).couleurTxt]" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            :d="getConfig(type.code).svgPath" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 :class="['font-display text-base font-extrabold transition group-hover:translate-x-1',
                                        getConfig(type.code).couleurTxt]">
                                        {{ type.nom }}
                                    </h3>
                                    <p class="mt-1.5 text-xs leading-relaxed text-text-sub">
                                        {{ getConfig(type.code).description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Flèche apparait au hover -->
                            <div
                                class="mt-4 flex items-center justify-end gap-1 text-xs font-bold text-moov-orange opacity-0 transition group-hover:opacity-100">
                                Choisir
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ── FOOTER ── -->
                <div class="border-t border-border-soft bg-page-bg/50 px-6 py-4 sm:px-8">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-text-sub">
                            <span class="font-bold">{{ types.length }} types</span> d'événements disponibles
                        </p>
                        <a href="/evenements" class="text-xs font-bold text-text-sub transition hover:text-moov-blue">
                            ← Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>