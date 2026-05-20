<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:       { type: Object, required: true },
    typesQuestions:  { type: Object, default: () => ({}) },
})

// ─── FORM ─────────────────────────────
const form = useForm({
    titre: '',
    type: 'satisfaction',
    items: [
        {
            type: 'note',
            label: '',
            echelle: 5,
            obligatoire: true,
            options: [],
        },
    ],
})

// ─── HELPERS ──────────────────────────
const typesEnquete = [
    { value: 'satisfaction', label: 'Satisfaction' },
    { value: 'evaluation', label: 'Évaluation' },
    { value: 'feedback', label: 'Feedback' },
]

const optionsParDefaut = (type) => {
    if (type === 'choix_unique' || type === 'choix_multiple') {
        return ['Option 1', 'Option 2']
    }
    return []
}

// ─── ACTIONS QUESTIONS ────────────────
const ajouterQuestion = () => {
    form.items.push({
        type: 'note',
        label: '',
        echelle: 5,
        obligatoire: false,
        options: [],
    })
}

const supprimerQuestion = (index) => {
    if (form.items.length === 1) {
        alert('Une enquête doit contenir au moins une question.')
        return
    }
    if (confirm('Supprimer cette question ?')) {
        form.items.splice(index, 1)
    }
}

const dupliquerQuestion = (index) => {
    const question = JSON.parse(JSON.stringify(form.items[index]))
    form.items.splice(index + 1, 0, question)
}

const onTypeChange = (index) => {
    const item = form.items[index]
    if (['choix_unique', 'choix_multiple'].includes(item.type)) {
        if (!item.options || item.options.length === 0) {
            item.options = optionsParDefaut(item.type)
        }
    } else if (item.type === 'note') {
        item.echelle = item.echelle ?? 5
    }
}

const ajouterOption = (index) => {
    form.items[index].options.push(`Option ${form.items[index].options.length + 1}`)
}

const supprimerOption = (qIndex, oIndex) => {
    if (form.items[qIndex].options.length <= 2) {
        alert('Il faut au moins 2 options.')
        return
    }
    form.items[qIndex].options.splice(oIndex, 1)
}

// ─── SUBMIT ───────────────────────────
const submit = () => {
    // Validation côté client
    if (!form.titre.trim()) {
        alert('Le titre est obligatoire.')
        return
    }

    for (const [index, item] of form.items.entries()) {
        if (!item.label.trim()) {
            alert(`La question ${index + 1} doit avoir un libellé.`)
            return
        }
        if (['choix_unique', 'choix_multiple'].includes(item.type)) {
            const optionsValides = item.options.filter(o => o.trim() !== '')
            if (optionsValides.length < 2) {
                alert(`La question ${index + 1} doit avoir au moins 2 options.`)
                return
            }
        }
    }

    form.post(`/evenements/${props.evenement.id}/communication/enquetes`, {
        forceFormData: false,
    })
}

const annuler = () => {
    if (confirm('Annuler la création ? Les données seront perdues.')) {
        router.visit(`/evenements/${props.evenement.id}/communication/enquetes`)
    }
}


const labelTypeQuestion = (type) => ({
    note:           ' Note',
    choix_unique:   ' Choix unique',
    choix_multiple: ' Choix multiples',
    texte_court:    ' Texte court',
    texte_long:     ' Texte long',
    oui_non:        ' Oui/Non',
}[type] || type)
</script>

<template>
    <DashboardLayout>

        <!-- ─── RETOUR ─── -->
        <Link :href="`/evenements/${evenement.id}/communication/enquetes`"
              class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub hover:text-moov-blue">
            ← Retour aux enquêtes
        </Link>

        <!-- ─── EN-TÊTE ─── -->
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Communication · Nouvelle enquête
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Créer une enquête
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Pour l'événement : {{ evenement.titre }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">

            <!-- ═════ INFOS GÉNÉRALES ═════ -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <h2 class="font-display text-base font-extrabold text-text-main">
                    Informations générales
                </h2>
                <p class="mt-1 text-xs text-text-sub">
                    Les bases de votre enquête
                </p>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Titre de l'enquête *
                        </label>
                        <input v-model="form.titre" type="text" required
                               placeholder="Enquête de satisfaction - Conférence RSE"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        <p v-if="form.errors.titre" class="mt-1 text-xs text-red-600">{{ form.errors.titre }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Type
                        </label>
                        <select v-model="form.type"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                            <option v-for="t in typesEnquete" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ═════ QUESTIONS ═════ -->
            <div class="rounded-xl bg-card p-6 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Questions
                            <span class="ml-2 rounded-full bg-moov-blue/10 px-2 py-0.5 text-xs font-bold text-moov-blue">
                                {{ form.items.length }}
                            </span>
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Ajoutez les questions à poser aux participants
                        </p>
                    </div>
                </div>

                <!-- ─── Liste des questions ─── -->
                <div class="mt-5 space-y-4">

                    <div v-for="(item, index) in form.items" :key="index"
                         class="rounded-xl border-2 border-border-soft bg-page-bg/30 p-4 transition hover:border-moov-blue/30">

                        <!-- Header question -->
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-moov-blue text-xs font-extrabold text-white">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-text-sub">
                                    {{ labelTypeQuestion(item.type) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="dupliquerQuestion(index)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-blue-50 hover:text-blue-600"
                                        title="Dupliquer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                <button type="button" @click="supprimerQuestion(index)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-red-50 hover:text-red-600"
                                        title="Supprimer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Champs de la question -->
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-sub">
                                    Libellé de la question *
                                </label>
                                <input v-model="item.label" type="text" required
                                       placeholder="Quelle est votre note globale ?"
                                       class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-sub">
                                    Type de réponse
                                </label>
                                <select v-model="item.type" @change="onTypeChange(index)"
                                        class="w-full rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                    <option v-for="(label, value) in typesQuestions" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Échelle (si type = note) -->
                        <div v-if="item.type === 'note'" class="mt-3">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-sub">
                                Échelle (max)
                            </label>
                            <select v-model.number="item.echelle"
                                    class="w-32 rounded-lg border border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                <option :value="3">3</option>
                                <option :value="5">5 (étoiles)</option>
                                <option :value="10">10</option>
                            </select>
                        </div>

                        <!-- Options (si choix unique/multiple) -->
                        <div v-if="['choix_unique', 'choix_multiple'].includes(item.type)" class="mt-3">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-text-sub">
                                Options
                            </label>
                            <div class="space-y-2">
                                <div v-for="(option, oIndex) in item.options" :key="oIndex"
                                     class="flex items-center gap-2">
                                    <span class="text-xs text-text-muted w-5">{{ oIndex + 1 }}.</span>
                                    <input v-model="item.options[oIndex]" type="text"
                                           placeholder="Texte de l'option"
                                           class="flex-1 rounded-lg border border-border-soft bg-white px-3 py-1.5 text-sm outline-none focus:border-moov-blue"/>
                                    <button type="button" @click="supprimerOption(index, oIndex)"
                                            class="rounded p-1.5 text-text-sub hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <button type="button" @click="ajouterOption(index)"
                                        class="text-xs font-bold text-moov-blue hover:underline">
                                     Ajouter une option
                                </button>
                            </div>
                        </div>

                        <!-- Obligatoire -->
                        <div class="mt-3">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                <input v-model="item.obligatoire" type="checkbox"
                                       class="h-4 w-4 rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                                <span class="font-bold text-text-main">Question obligatoire</span>
                            </label>
                        </div>

                        <!-- APERÇU -->
                        <div class="mt-4 rounded-lg border border-dashed border-moov-blue/30 bg-moov-blue/5 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-moov-blue">
                                Aperçu
                            </p>
                            <p class="mt-1 text-sm font-bold text-text-main">
                                {{ item.label || '(Libellé de la question)' }}
                                <span v-if="item.obligatoire" class="text-red-600">*</span>
                            </p>

                            <div class="mt-2">
                                <!-- Note -->
                                <div v-if="item.type === 'note'" class="flex gap-1 text-2xl text-amber-400">
                                    <span v-for="n in (item.echelle || 5)" :key="n">★</span>
                                </div>

                                <!-- Choix unique -->
                                <div v-else-if="item.type === 'choix_unique'" class="space-y-1">
                                    <label v-for="(opt, oi) in item.options" :key="oi"
                                           class="flex items-center gap-2 text-sm text-text-sub">
                                        <input type="radio" disabled class="h-4 w-4"/>
                                        {{ opt || `(Option ${oi + 1})` }}
                                    </label>
                                </div>

                                <!-- Choix multiple -->
                                <div v-else-if="item.type === 'choix_multiple'" class="space-y-1">
                                    <label v-for="(opt, oi) in item.options" :key="oi"
                                           class="flex items-center gap-2 text-sm text-text-sub">
                                        <input type="checkbox" disabled class="h-4 w-4 rounded"/>
                                        {{ opt || `(Option ${oi + 1})` }}
                                    </label>
                                </div>

                                <!-- Texte court -->
                                <input v-else-if="item.type === 'texte_court'" type="text" disabled
                                       placeholder="Réponse courte..."
                                       class="w-full rounded border border-border-soft bg-white px-2 py-1 text-sm"/>

                                <!-- Texte long -->
                                <textarea v-else-if="item.type === 'texte_long'" disabled rows="2"
                                          placeholder="Réponse détaillée..."
                                          class="w-full rounded border border-border-soft bg-white px-2 py-1 text-sm"/>

                                <!-- Oui/Non -->
                                <div v-else-if="item.type === 'oui_non'" class="flex gap-2">
                                    <button type="button" disabled
                                            class="rounded-lg border-2 border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm font-bold text-emerald-700">
                                        ✓ Oui
                                    </button>
                                    <button type="button" disabled
                                            class="rounded-lg border-2 border-red-200 bg-red-50 px-4 py-1.5 text-sm font-bold text-red-700">
                                        ✕ Non
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton ajouter question -->
                    <button type="button" @click="ajouterQuestion"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-moov-blue/40 bg-moov-blue/5 px-4 py-4 text-sm font-bold text-moov-blue transition hover:bg-moov-blue/10">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter une question
                    </button>
                </div>
            </div>

            <!-- ─── ACTIONS ─── -->
            <div class="flex justify-end gap-3">
                <button type="button" @click="annuler"
                        class="rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:border-text-sub">
                    Annuler
                </button>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-moov-blue px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700 disabled:opacity-50">
                    {{ form.processing ? 'Création...' : '💾 Créer l\'enquête' }}
                </button>
            </div>
        </form>

    </DashboardLayout>
</template>