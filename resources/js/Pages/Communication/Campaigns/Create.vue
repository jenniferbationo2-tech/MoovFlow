<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:        { type: Object, required: true },
    compteurs:        { type: Object, default: () => ({}) },
    cibles:           { type: Object, default: () => ({}) },
    modelesPredefinis: { type: Object, default: () => ({}) },
    variablesDispos:  { type: Object, default: () => ({}) },
})

// ─── FORM ──────────────────────────
const form = useForm({
    objet:              '',
    contenu:            '',
    mode_destinataires: 'valides',
    action:             'brouillon',
})

const modeleSelectionne = ref('vierge')

// ─── MODELES ───────────────────────
const choisirModele = (cle) => {
    modeleSelectionne.value = cle
    if (cle === 'vierge') {
        form.objet = ''
        form.contenu = ''
        return
    }
    if (props.modelesPredefinis[cle]) {
        form.objet = props.modelesPredefinis[cle].objet
        form.contenu = props.modelesPredefinis[cle].contenu
    }
}

// ─── COMPTEUR DESTINATAIRES ────────
const nbDestinataires = computed(() =>
    props.compteurs[form.mode_destinataires] ?? 0
)

// ─── INSERTION DE VARIABLES ────────
const champActif = ref('objet')
const textareaRef = ref(null)
const objetRef = ref(null)

const insererVariable = (variable) => {
    if (champActif.value === 'objet') {
        form.objet = (form.objet || '') + ' ' + variable
    } else {
        form.contenu = (form.contenu || '') + variable
    }
}

// ─── PREVIEW LIVE ──────────────────
const preview = ref({
    destinataire: '',
    objet: '',
    corps: '',
})

const previewLoading = ref(false)

const fetchPreview = async () => {
    if (!form.objet && !form.contenu) {
        preview.value = { destinataire: '', objet: '', corps: '' }
        return
    }

    previewLoading.value = true
    try {
        const response = await fetch('/evenements/' + props.evenement.id + '/communication/campaigns/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                evenement_id: props.evenement.id,
                objet: form.objet || ' ',
                contenu: form.contenu || ' ',
            }),
        })

        if (response.ok) {
            const data = await response.json()
            preview.value = data
        }
    } catch (e) {
        console.error('Erreur preview', e)
    }
    previewLoading.value = false
}

// Debounce du preview (500ms après le dernier changement)
let debounceTimer = null
watch([() => form.objet, () => form.contenu], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchPreview, 500)
})

// Préview initial si modèle choisi
const choisirModeleEtPreview = (cle) => {
    choisirModele(cle)
    setTimeout(fetchPreview, 100)
}

// ─── SUBMIT ────────────────────────
const enregistrer = (action) => {
    form.action = action

    if (action === 'envoyer') {
        if (nbDestinataires.value === 0) {
            alert('Aucun destinataire pour cette cible. Choisissez une autre cible.')
            return
        }
        if (!confirm(`Envoyer cette campagne à ${nbDestinataires.value} personne(s) maintenant ?\n\nCette action est immédiate et irréversible.`)) return
    }

    form.post(`/evenements/${props.evenement.id}/communication/campaigns/send`)
}
</script>

<template>
    <DashboardLayout>

        <!-- ─── RETOUR ─── -->
        <Link :href="`/evenements/${evenement.id}/communication/campaigns`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ←Retour aux campagnes
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Communication · Nouvelle campagne
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                 Nouvelle campagne email
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Événement : {{ evenement.titre }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- ═════ COLONNE GAUCHE (Form) ═════ -->
            <div class="space-y-6 lg:col-span-2">

                <!-- ─── 1. MODÈLES ─── -->
                <div class="rounded-xl bg-card p-5 shadow-card">
                    <h2 class="font-display text-sm font-extrabold text-text-main">
                         Modèle (optionnel)
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">
                        Démarrer avec un modèle pré-conçu ou partir de zéro
                    </p>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <button type="button" @click="choisirModeleEtPreview('vierge')"
                                :class="['rounded-lg border-2 px-3 py-3 text-xs font-bold transition',
                                    modeleSelectionne === 'vierge'
                                        ? 'border-moov-blue bg-moov-blue/10 text-moov-blue'
                                        : 'border-border-soft bg-white text-text-sub hover:border-moov-blue/40']">
                            Vide
                        </button>
                        <button type="button" @click="choisirModeleEtPreview('rappel_j1')"
                                :class="['rounded-lg border-2 px-3 py-3 text-xs font-bold transition',
                                    modeleSelectionne === 'rappel_j1'
                                        ? 'border-moov-blue bg-moov-blue/10 text-moov-blue'
                                        : 'border-border-soft bg-white text-text-sub hover:border-moov-blue/40']">
                            Rappel J-1
                        </button>
                        <button type="button" @click="choisirModeleEtPreview('remerciement')"
                                :class="['rounded-lg border-2 px-3 py-3 text-xs font-bold transition',
                                    modeleSelectionne === 'remerciement'
                                        ? 'border-moov-blue bg-moov-blue/10 text-moov-blue'
                                        : 'border-border-soft bg-white text-text-sub hover:border-moov-blue/40']">
                             Remerciement
                        </button>
                    </div>
                </div>

                <!-- ─── 2. CIBLE ─── -->
                <div class="rounded-xl bg-card p-5 shadow-card">
                    <h2 class="font-display text-sm font-extrabold text-text-main">
                         Cible
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">
                        À qui envoyer cette campagne ?
                    </p>

                    <div class="mt-4 space-y-2">
                        <label v-for="(label, key) in cibles" :key="key"
                               :class="['flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 transition',
                                   form.mode_destinataires === key
                                       ? 'border-moov-blue bg-moov-blue/5'
                                       : 'border-border-soft bg-white hover:border-moov-blue/30']">
                            <div class="flex items-center gap-3">
                                <input type="radio" :value="key" v-model="form.mode_destinataires"
                                       class="h-4 w-4 text-moov-blue focus:ring-moov-blue"/>
                                <span class="text-sm font-bold text-text-main">{{ label }}</span>
                            </div>
                            <span :class="['inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-bold',
                                (compteurs[key] ?? 0) > 0
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-slate-100 text-slate-500']">
                                {{ compteurs[key] ?? 0 }}
                            </span>
                        </label>
                    </div>

                    <div v-if="nbDestinataires === 0"
                         class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-3">
                        <p class="text-xs text-amber-700">
                            Aucun destinataire pour cette cible. Choisissez-en une autre pour pouvoir envoyer.
                        </p>
                    </div>
                </div>

                <!-- ─── 3. CONTENU ─── -->
                <div class="rounded-xl bg-card p-5 shadow-card">
                    <h2 class="font-display text-sm font-extrabold text-text-main">
                         Contenu du message
                    </h2>
                    <p class="mt-1 text-xs text-text-sub">
                        Personnalisez l'objet et le corps de l'email
                    </p>

                    <div class="mt-4 space-y-4">
                        <!-- Objet -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Objet *
                            </label>
                            <input v-model="form.objet" type="text" required ref="objetRef"
                                   @focus="champActif = 'objet'"
                                   placeholder="Ex: Rappel - Conférence demain !"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            <p v-if="form.errors.objet" class="mt-1 text-xs text-red-600">{{ form.errors.objet }}</p>
                        </div>

                        <!-- Corps -->
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Message *
                            </label>
                            <textarea v-model="form.contenu" required rows="10" ref="textareaRef"
                                      @focus="champActif = 'contenu'"
                                      placeholder="Bonjour {prenom},

Nous vous attendons demain à l'événement..."
                                      class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm font-mono outline-none focus:border-moov-blue"/>
                            <p v-if="form.errors.contenu" class="mt-1 text-xs text-red-600">{{ form.errors.contenu }}</p>
                        </div>

                        <!-- Variables disponibles -->
                        <div class="rounded-lg bg-moov-blue/5 border border-moov-blue/20 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-moov-blue">
                                 Variables disponibles
                            </p>
                            <p class="mt-1 text-xs text-text-sub">
                                Clique pour insérer dans le champ actif
                                <strong class="text-moov-blue">({{ champActif === 'objet' ? 'Objet' : 'Message' }})</strong>
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <button v-for="(desc, variable) in variablesDispos" :key="variable"
                                        type="button" @click="insererVariable(variable)"
                                        :title="desc"
                                        class="rounded-md bg-white border border-moov-blue/30 px-2 py-1 text-[11px] font-bold font-mono text-moov-blue transition hover:bg-moov-blue hover:text-white">
                                    {{ variable }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── ACTIONS ─── -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="enregistrer('brouillon')"
                            :disabled="form.processing || !form.objet || !form.contenu"
                            class="rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:border-text-sub disabled:opacity-50">
                         Enregistrer brouillon
                    </button>
                    <button type="button" @click="enregistrer('envoyer')"
                            :disabled="form.processing || !form.objet || !form.contenu || nbDestinataires === 0"
                            class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50">
                         Envoyer maintenant ({{ nbDestinataires }})
                    </button>
                </div>
            </div>

            <!-- ═════ COLONNE DROITE (Preview) ═════ -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 rounded-xl bg-card p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-sm font-extrabold text-text-main">
                             Aperçu en direct
                        </h2>
                        <span v-if="previewLoading" class="text-xs text-text-muted">
                             Mise à jour...
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-text-sub">
                        Avec les variables remplies pour un participant
                    </p>

                    <!-- Carte email simulée -->
                    <div class="mt-4 rounded-lg border border-border-soft bg-white p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                            À
                        </p>
                        <p class="font-mono text-xs text-text-main">
                            {{ preview.destinataire || '[Pas de destinataire test]' }}
                        </p>

                        <div class="mt-3 border-t border-border-soft pt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Objet
                            </p>
                            <p class="mt-1 font-bold text-text-main">
                                {{ preview.objet || '(L\'objet apparaîtra ici)' }}
                            </p>
                        </div>

                        <div class="mt-3 border-t border-border-soft pt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Message
                            </p>
                            <pre class="mt-1 whitespace-pre-wrap text-xs text-text-main font-sans leading-relaxed">{{ preview.corps || '(Le message apparaîtra ici)' }}</pre>
                        </div>
                    </div>

                    <p class="mt-3 text-[10px] italic text-text-muted">
                         L'aperçu se met à jour automatiquement
                    </p>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>