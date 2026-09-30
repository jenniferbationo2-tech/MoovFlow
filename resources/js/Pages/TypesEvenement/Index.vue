<script setup>
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    types: { type: Array, default: () => [] },
})

const form = useForm({
    nom: '',
    code: '',
})

// Suggestion automatique du code à partir du nom (modifiable par l'utilisateur)
const codeModifieManuel = { value: false }

const suggererCode = () => {
    if (codeModifieManuel.value) return
    const sansAccents = form.nom.normalize('NFD').replace(/[̀-ͯ]/g, '')
    form.code = sansAccents
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
}

const onCodeInput = () => {
    codeModifieManuel.value = true
}

const creerType = () => {
    form.post('/types-evenement', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            codeModifieManuel.value = false
        },
    })
}

const supprimerType = (type) => {
    if (type.evenements_count > 0) return
    if (confirm(`Supprimer la typologie "${type.nom}" ?`)) {
        router.delete(`/types-evenement/${type.id}`, { preserveScroll: true })
    }
}

const totalTypes = computed(() => props.types.length)
</script>

<template>
    <DashboardLayout>

        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold text-text-main">
                Typologies d'Événements
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Créez de nouvelles catégories d'événements disponibles dans toute la plateforme
                ({{ totalTypes }} typologie{{ totalTypes > 1 ? 's' : '' }} actuellement)
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- ── FORMULAIRE DE CRÉATION ── -->
            <div class="lg:col-span-1">
                <div class="rounded-2xl bg-card p-6 shadow-card">
                    <h2 class="font-display text-lg font-extrabold text-text-main">
                        Nouvelle typologie
                    </h2>
                    <p class="mt-1 text-sm text-text-sub">
                        Elle sera immédiatement proposée dans "Nouvel Événement".
                    </p>

                    <form @submit.prevent="creerType" class="mt-5 space-y-4">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Nom de la typologie *
                            </label>
                            <input v-model="form.nom" @input="suggererCode" type="text" required
                                   placeholder="Ex: Webinaire Grand Public"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2.5 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            <p v-if="form.errors.nom" class="mt-1 text-xs text-red-600">{{ form.errors.nom }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Code technique *
                            </label>
                            <input v-model="form.code" @input="onCodeInput" type="text" required
                                   placeholder="Ex: WEBINAIRE_GP"
                                   class="w-full rounded-lg border-2 border-border-soft px-3 py-2.5 font-mono text-sm uppercase outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            <p class="mt-1 text-xs text-text-muted">
                                Identifiant unique, généré automatiquement à partir du nom (modifiable).
                            </p>
                            <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                        </div>

                        <button type="submit" :disabled="form.processing"
                                class="w-full rounded-lg bg-moov-noir px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-50">
                            Créer la typologie
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── LISTE DES TYPOLOGIES EXISTANTES ── -->
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-2xl border border-border-soft bg-card shadow-card">
                    <div class="divide-y divide-border-soft">
                        <div v-for="type in types" :key="type.id"
                             class="flex items-center justify-between gap-3 px-5 py-4 transition hover:bg-page-bg/40">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-text-main">{{ type.nom }}</p>
                                <p class="mt-0.5 font-mono text-xs text-text-muted">{{ type.code }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                {{ type.evenements_count }} événement{{ type.evenements_count > 1 ? 's' : '' }}
                            </span>
                            <button v-if="type.evenements_count === 0" @click="supprimerType(type)"
                                    class="shrink-0 rounded-lg p-2 text-text-muted transition hover:bg-red-50 hover:text-red-600"
                                    title="Supprimer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z"/>
                                </svg>
                            </button>
                            <span v-else class="shrink-0 w-8"/>
                        </div>
                    </div>

                    <div v-if="!types.length" class="py-16 text-center">
                        <p class="font-bold text-text-main">Aucune typologie</p>
                        <p class="mt-1 text-sm text-text-sub">Créez la première via le formulaire.</p>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>
