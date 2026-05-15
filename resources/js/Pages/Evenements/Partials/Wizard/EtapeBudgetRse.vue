<script setup>
const props = defineProps({
    form: { type: Object, required: true },
    type: { type: Object, required: true },
})

defineEmits(['precedent', 'suivant'])

const ajouterTarif = () => {
    props.form.tarifs.push({ nom: '', montant: 0 })
}
const retirerTarif = (i) => {
    props.form.tarifs.splice(i, 1)
}

// Pas de tarif pour SALON
const sansTarifs = props.type.code === 'SALON'
</script>

<template>
    <div class="rounded-2xl bg-white shadow-card">

        <div class="border-b border-border-soft p-6">
            <h2 class="font-display text-2xl font-extrabold text-text-main">
                Budget & Objectifs RSE
            </h2>
            <p class="mt-1 text-sm text-text-sub">
                Coûts prévisionnels et impact social attendu
            </p>
        </div>

        <div class="space-y-6 p-6">

            <!-- BUDGET -->
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                    Budget prévisionnel total (FCFA)
                </label>
                <div class="relative">
                    <input v-model="form.budget_previsionnel" type="text" inputmode="numeric"
                           @input="form.budget_previsionnel = $event.target.value.replace(/\D/g, '')"
                           placeholder="Ex: 5000000"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 pr-16 text-base font-medium outline-none transition focus:border-moov-blue"/>
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-muted">FCFA</span>
                </div>
                <p v-if="form.budget_previsionnel > 0" class="mt-1 text-xs text-text-sub">
                    Soit <strong>{{ Number(form.budget_previsionnel).toLocaleString('fr-FR') }}</strong> FCFA
                </p>
            </div>

            <!-- TARIFS (sauf SALON) -->
            <div v-if="!sansTarifs">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">
                            Tarifs d'inscription
                        </p>
                        <p class="text-xs text-text-muted">Standard, VIP, Réduit... ou laisser vide pour gratuit</p>
                    </div>
                    <button @click="ajouterTarif" type="button"
                            class="rounded-lg bg-moov-noir px-3 py-2 text-xs font-bold text-white transition hover:bg-moov-noir-soft">
                        + Ajouter
                    </button>
                </div>

                <div v-if="form.tarifs.length === 0"
                     class="rounded-xl border-2 border-dashed border-border-soft bg-page-bg/30 p-6 text-center">
                    <p class="text-sm font-bold text-text-sub">Événement gratuit</p>
                    <p class="mt-1 text-xs text-text-muted">Cliquez sur "Ajouter" pour créer un tarif payant</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="(t, i) in form.tarifs" :key="i"
                         class="flex items-end gap-3 rounded-xl border border-border-soft bg-white p-4">
                        <div class="flex-1">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Nom du tarif
                            </label>
                            <input v-model="t.nom" type="text" placeholder="Ex: Standard, VIP, Étudiant..."
                                   class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div class="w-40">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Montant (FCFA)
                            </label>
                            <input v-model="t.montant" type="text" inputmode="numeric"
                                   @input="t.montant = $event.target.value.replace(/\D/g, '')"
                                   class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-3 py-2 text-right text-sm font-bold outline-none focus:border-moov-blue"/>
                        </div>
                        <button @click="retirerTarif(i)" type="button"
                                class="rounded-lg bg-red-50 p-2.5 text-red-600 transition hover:bg-red-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- OBJECTIFS RSE -->
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <p class="mb-4 font-display text-sm font-extrabold text-blue-900">
                    Objectifs RSE
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Public cible
                        </label>
                        <input v-model="form.public_cible" type="text"
                               placeholder="Ex: Femmes entrepreneures, Étudiants en informatique..."
                               class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Cible de bénéficiaires (estimation)
                        </label>
                        <input v-model="form.cible_beneficiaires" type="text" inputmode="numeric"
                               @input="form.cible_beneficiaires = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 200"
                               class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Objectifs principaux
                        </label>
                        <textarea v-model="form.objectifs_principaux" rows="4"
                                  placeholder="Ex: Sensibiliser 200 jeunes, Identifier 5 talents, Distribuer 100 bourses..."
                                  class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>
            </div>
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
                    class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark">
                Suivant : Configuration spécifique
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</template>