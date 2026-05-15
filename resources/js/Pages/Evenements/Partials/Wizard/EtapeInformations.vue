<script setup>
import { ref } from 'vue'

const props = defineProps({
    form:    { type: Object, required: true },
    erreurs: { type: Array, default: () => [] },
    type:    { type: Object, required: true },
})

defineEmits(['suivant'])

const previewVisuel = ref(props.form.visuel || null)
const onVisuelChange = (e) => {
    const file = e.target.files[0]
    props.form.visuel = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => previewVisuel.value = ev.target.result
        reader.readAsDataURL(file)
    }
}

const reglementNom = ref(null)
const onReglementChange = (e) => {
    const file = e.target.files[0]
    props.form.reglement_pdf = file
    reglementNom.value = file?.name ?? null
}
</script>

<template>
    <div class="rounded-2xl bg-white shadow-card">

        <div class="border-b border-border-soft p-6">
            <h2 class="font-display text-2xl font-extrabold text-text-main">
                Informations générales
            </h2>
            <p class="mt-1 text-sm text-text-sub">
                Renseignez les détails clés de votre {{ type.nom.toLowerCase() }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">

            <!-- COLONNE GAUCHE -->
            <div class="space-y-5 lg:col-span-2">

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Titre de l'événement *
                    </label>
                    <input v-model="form.titre" type="text" required
                           :placeholder="`Ex: ${type.nom} 2026`"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Description
                    </label>
                    <textarea v-model="form.description" rows="6"
                              placeholder="Décrivez l'événement, ses objectifs, son public cible..."
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Règlement de l'événement (PDF)
                    </label>
                    <div class="rounded-lg border-2 border-dashed border-border-soft bg-page-bg/30 p-4">
                        <input @change="onReglementChange" type="file" accept="application/pdf"
                               class="block w-full text-sm text-text-sub file:mr-3 file:rounded-lg file:border-0 file:bg-moov-blue file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:tracking-wider file:text-white"/>
                        <p v-if="reglementNom" class="mt-2 text-xs text-emerald-600">
                            ✓ {{ reglementNom }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : VISUEL -->
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                    Visuel
                </label>
                <label class="block cursor-pointer">
                    <div :class="['relative flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition',
                        previewVisuel ? 'border-moov-blue' : 'border-border-soft bg-page-bg hover:bg-page-bg/70']">
                        <img v-if="previewVisuel" :src="previewVisuel" class="h-full w-full object-cover"/>
                        <div v-else class="text-center">
                            <svg class="mx-auto h-10 w-10 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                            <p class="mt-2 text-sm font-bold text-text-sub">Ajouter une image</p>
                            <p class="text-xs text-text-muted">PNG, JPG · Max 5 Mo</p>
                        </div>
                    </div>
                    <input @change="onVisuelChange" type="file" accept="image/*" class="hidden"/>
                </label>
            </div>
        </div>

        <!-- Erreurs -->
        <div v-if="erreurs.length > 0" class="border-t border-border-soft bg-red-50 p-4">
            <ul class="space-y-1 text-sm text-red-700">
                <li v-for="(e, i) in erreurs" :key="i">• {{ e }}</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end border-t border-border-soft bg-page-bg/30 p-6">
            <button @click="$emit('suivant')"
                    :disabled="erreurs.length > 0"
                    class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark disabled:cursor-not-allowed disabled:opacity-50">
                Suivant : Lieu & Dates
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</template>