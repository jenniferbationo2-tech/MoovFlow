<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    intervenants: Object,
    stats:        Object,
    filters:      Object,
})

const recherche = ref(props.filters?.search ?? '')
const filtrer = () => {
    router.get('/vivier/intervenants', { search: recherche.value || undefined },
        { preserveState: true, preserveScroll: true })
}

const modalOuvert = ref(false)
const form = useForm({
    nom: '', prenom: '', email: '', telephone: '',
    specialite: '', biographie: '', tarif_jour: 0, role: 'intervenant',
})

const ouvrirModal = () => {
    form.reset()
    form.role = 'intervenant'
    modalOuvert.value = true
}
const creer = () => {
    form.post('/vivier/intervenants', {
        onSuccess: () => modalOuvert.value = false,
        preserveScroll: true,
    })
}
const supprimer = (i) => {
    if (confirm(`Supprimer ${i.prenom} ${i.nom} ?`)) {
        router.delete(`/vivier/intervenants/${i.id}`)
    }
}

const initiales = (i) => `${i.prenom?.[0] ?? ''}${i.nom?.[0] ?? ''}`.toUpperCase()
const couleurRole = (r) => ({
    intervenant: 'bg-blue-50 text-blue-700',
    jury:        'bg-violet-50 text-violet-700',
    formateur:   'bg-emerald-50 text-emerald-700',
}[r] || 'bg-slate-50 text-slate-700')
const labelRole = (r) => ({
    intervenant: 'Intervenant',
    jury:        'Membre du jury',
    formateur:   'Formateur',
}[r] || r)
</script>

<template>
    <DashboardLayout>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-extrabold text-text-main">Vivier d'Intervenants & Jury</h1>
                <p class="mt-1 text-sm text-text-sub">Experts disponibles pour conférences, formations et jurys</p>
            </div>
            <button @click="ouvrirModal"
                    class="rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                + Nouvel Intervenant
            </button>
        </div>

        <div class="mb-6 grid grid-cols-4 gap-3">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Intervenants</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-blue-600">{{ stats.intervenants }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Jury</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-violet-600">{{ stats.jury }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Formateurs</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.formateurs }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft p-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-text-main">Liste des intervenants</h2>
                    <input v-model="recherche" @keyup.enter="filtrer"
                           type="search" placeholder="Nom, spécialité..."
                           class="w-64 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue"/>
                </div>
            </div>

            <div v-if="intervenants.data?.length" class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in intervenants.data" :key="i.id"
                     class="rounded-xl border border-border-soft bg-white p-4 transition hover:shadow-card-hover">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-sm font-bold text-white">
                                {{ initiales(i) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-text-main">{{ i.prenom }} {{ i.nom }}</p>
                                <span :class="['mt-1 inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                    couleurRole(i.role)]">
                                    {{ labelRole(i.role) }}
                                </span>
                            </div>
                        </div>
                        <button @click="supprimer(i)"
                                class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-text-sub">
                        <p v-if="i.specialite"><strong>Spécialité :</strong> {{ i.specialite }}</p>
                        <p v-if="i.email">{{ i.email }}</p>
                        <p v-if="i.telephone">{{ i.telephone }}</p>
                    </div>
                    <p v-if="i.biographie" class="mt-2 text-xs text-text-sub line-clamp-3">{{ i.biographie }}</p>
                    <p v-if="i.tarif_jour > 0" class="mt-2 text-xs font-bold text-moov-blue">
                        {{ Number(i.tarif_jour).toLocaleString('fr-FR') }} F / jour
                    </p>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun intervenant dans le vivier</p>
                <p class="mt-1 text-sm text-text-sub">Ajoutez vos premiers experts</p>
            </div>
        </div>

        <!-- Modale -->
        <div v-if="modalOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouvel intervenant</h3>
                </div>
                <form @submit.prevent="creer" class="space-y-4 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Type *</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label v-for="r in [
                                { value: 'intervenant', label: 'Intervenant' },
                                { value: 'jury', label: 'Jury' },
                                { value: 'formateur', label: 'Formateur' },
                            ]" :key="r.value"
                            :class="['cursor-pointer rounded-lg border-2 p-2 text-center text-xs font-bold transition',
                                form.role === r.value ? 'border-moov-blue bg-moov-blue-50 text-moov-blue' : 'border-border-soft text-text-sub']">
                                <input type="radio" v-model="form.role" :value="r.value" class="sr-only"/>
                                {{ r.label }}
                            </label>
                        </div>
                    </div>
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
                            <input v-model="form.telephone" type="tel"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Spécialité</label>
                        <input v-model="form.specialite" type="text" placeholder="Marketing digital, Finance, IA..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Biographie</label>
                        <textarea v-model="form.biographie" rows="3" placeholder="Parcours, expérience..."
                                  class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Tarif journalier (FCFA)</label>
                        <input v-model.number="form.tarif_jour" type="number" min="0"
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