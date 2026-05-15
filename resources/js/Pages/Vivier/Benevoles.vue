<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    benevoles: Object,
    stats:     Object,
    filters:   Object,
})

const recherche = ref(props.filters?.search ?? '')
const filtrer = () => {
    router.get('/vivier/benevoles', { search: recherche.value || undefined },
        { preserveState: true, preserveScroll: true })
}

const modalOuvert = ref(false)
const form = useForm({
    nom: '', prenom: '', email: '', telephone: '',
    poste_affecte: '', disponibilites: '', competences: '',
})

const ouvrirModal = () => {
    form.reset()
    modalOuvert.value = true
}
const creer = () => {
    form.post('/vivier/benevoles', {
        onSuccess: () => modalOuvert.value = false,
        preserveScroll: true,
    })
}
const supprimer = (b) => {
    if (confirm(`Supprimer ${b.prenom} ${b.nom} du vivier ?`)) {
        router.delete(`/vivier/benevoles/${b.id}`)
    }
}

const initiales = (b) => `${b.prenom?.[0] ?? ''}${b.nom?.[0] ?? ''}`.toUpperCase()
const formaterDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
</script>

<template>
    <DashboardLayout>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-extrabold text-text-main">Vivier de Bénévoles</h1>
                <p class="mt-1 text-sm text-text-sub">Gestion des bénévoles disponibles pour les événements</p>
            </div>
            <button @click="ouvrirModal"
                    class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                + Nouveau Bénévole
            </button>
        </div>

        <div class="mb-6 grid grid-cols-3 gap-3">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Vivier libre</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.sans_event }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Affectés</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-orange-600">{{ stats.avec_event }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft p-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-text-main">Liste des bénévoles</h2>
                    <input v-model="recherche" @keyup.enter="filtrer"
                           type="search" placeholder="Nom, email, téléphone..."
                           class="w-64 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue"/>
                </div>
            </div>

            <div v-if="benevoles.data?.length" class="divide-y divide-border-soft">
                <div v-for="b in benevoles.data" :key="b.id"
                     class="flex items-start gap-4 p-4 transition hover:bg-page-bg/50">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">
                        {{ initiales(b) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-text-main">{{ b.prenom }} {{ b.nom }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-text-sub">
                            <span v-if="b.email">{{ b.email }}</span>
                            <span v-if="b.telephone">·</span>
                            <span v-if="b.telephone">{{ b.telephone }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span v-if="b.poste_affecte"
                                  class="rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                {{ b.poste_affecte }}
                            </span>
                            <span v-if="b.disponibilites"
                                  class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                                Dispo : {{ b.disponibilites }}
                            </span>
                            <span v-if="!b.evenement_id"
                                  class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                personne libre
                            </span>
                        </div>
                        <p v-if="b.competences" class="mt-2 text-xs text-text-sub line-clamp-2">
                            {{ b.competences }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs text-text-muted">{{ formaterDate(b.created_at) }}</p>
                        <button @click="supprimer(b)"
                                class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun bénévole dans le vivier</p>
                <p class="mt-1 text-sm text-text-sub">Commencez par enregistrer vos premiers bénévoles</p>
            </div>
        </div>

        <!-- Modale création -->
        <div v-if="modalOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouveau bénévole</h3>
                </div>
                <form @submit.prevent="creer" class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom *</label>
                            <input v-model="form.nom" type="text" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Prénom *</label>
                            <input v-model="form.prenom" type="text" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Email</label>
                            <input v-model="form.email" type="email"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Téléphone</label>
                            <input v-model="form.telephone" type="tel" placeholder="+226 70 12 34 56"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Poste préféré</label>
                        <input v-model="form.poste_affecte" type="text" placeholder="Accueil, Logistique, Sécurité..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Disponibilités</label>
                        <input v-model="form.disponibilites" type="text" placeholder="Week-ends, Soirées, Vacances..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Compétences</label>
                        <textarea v-model="form.competences" rows="2"
                                  placeholder="Langues parlées, expériences, etc."
                                  class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creer" :disabled="form.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Ajouter 
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>