<script setup>
defineProps({
    form:    { type: Object, required: true },
    lieux:   { type: Array, required: true },
    erreurs: { type: Array, default: () => [] },
})

defineEmits(['precedent', 'suivant'])
</script>

<template>
    <div class="rounded-2xl bg-white shadow-card">

        <div class="border-b border-border-soft p-6">
            <h2 class="font-display text-2xl font-extrabold text-text-main">
                Lieu & Dates
            </h2>
            <p class="mt-1 text-sm text-text-sub">
                Quand et où aura lieu votre événement
            </p>
        </div>

        <div class="space-y-5 p-6">

            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                    Lieu *
                </label>
                <select v-model="form.lieu_id" required
                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                    <option value="">Sélectionner un lieu</option>
                    <option v-for="l in lieux" :key="l.id" :value="l.id">{{ l.nom }}</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Date de début *
                    </label>
                    <input v-model="form.date_debut" type="datetime-local" required
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Date de fin *
                    </label>
                    <input v-model="form.date_fin" type="datetime-local" required
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                    Capacité maximale
                </label>
                <input v-model="form.capacite_max" type="text" inputmode="numeric"
                       @input="form.capacite_max = $event.target.value.replace(/\D/g, '')"
                       placeholder="Nombre max de participants"
                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                <p class="mt-1 text-xs text-text-muted">Laisser vide pour illimité</p>
            </div>
        </div>

        <div v-if="erreurs.length > 0" class="border-t border-border-soft bg-red-50 p-4">
            <ul class="space-y-1 text-sm text-red-700">
                <li v-for="(e, i) in erreurs" :key="i">• {{ e }}</li>
            </ul>
        </div>

        <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
            <button @click="$emit('precedent')"
                    class="flex items-center gap-2 rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </button>
            <button @click="$emit('suivant')"
                    :disabled="erreurs.length > 0"
                    class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark disabled:opacity-50">
                Suivant : Budget & RSE
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</template>