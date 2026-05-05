<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    evenement:  Object,
    ressources: Array,
    dotations:  Array,
    benevoles:  Array,
})

// ── ONGLETS ──────────────────────────────
const ongletActif = ref('ressources')
const onglets = [
    { value: 'ressources', label: 'Ressources & Matériel' },
    { value: 'dotations',  label: 'Dotations' },
    { value: 'benevoles',  label: 'Bénévoles' },
]

// ── HELPERS ──────────────────────────────
const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const baseUrl = computed(() => `/evenements/${props.evenement.id}/logistique-v2`)

// ════════════════════════════════════════
//   RESSOURCES
// ════════════════════════════════════════
const modalRessourceOuvert = ref(false)
const formRessource = useForm({
    nom: '', type: 'materiel', quantite_totale: 1, unite: '',
    cout_unitaire: 0, fournisseur: '', observations: '',
})

const ouvrirModalRessource = () => {
    formRessource.reset()
    formRessource.type = 'materiel'
    modalRessourceOuvert.value = true
}

const creerRessource = () => {
    formRessource.post(`${baseUrl.value}/ressources`, {
        onSuccess: () => modalRessourceOuvert.value = false,
        preserveScroll: true,
    })
}

const supprimerRessource = (r) => {
    if (confirm(`Supprimer "${r.nom}" de l'inventaire ?`)) {
        router.delete(`${baseUrl.value}/ressources/${r.id}`)
    }
}

// ════════════════════════════════════════
//   DOTATIONS
// ════════════════════════════════════════
const modalDotationOuvert = ref(false)
const formDotation = useForm({
    beneficiaire: '', item: '', quantite: 1, taille: '', a_retourner: false,
})

const ouvrirModalDotation = () => {
    formDotation.reset()
    modalDotationOuvert.value = true
}

const creerDotation = () => {
    formDotation.post(`${baseUrl.value}/dotations`, {
        onSuccess: () => modalDotationOuvert.value = false,
        preserveScroll: true,
    })
}

const marquerRetour = (d) => {
    if (confirm(`Confirmer le retour de "${d.item}" par ${d.beneficiaire} ?`)) {
        router.patch(`${baseUrl.value}/dotations/${d.id}/return`)
    }
}

const supprimerDotation = (d) => {
    if (confirm('Supprimer cette dotation ?')) {
        router.delete(`${baseUrl.value}/dotations/${d.id}`)
    }
}

// ════════════════════════════════════════
//   BÉNÉVOLES
// ════════════════════════════════════════
const modalBenevoleOuvert = ref(false)
const formBenevole = useForm({
    nom: '', prenom: '', email: '', telephone: '',
    poste_affecte: '', horaires: '',
})

const ouvrirModalBenevole = () => {
    formBenevole.reset()
    modalBenevoleOuvert.value = true
}

const creerBenevole = () => {
    formBenevole.post(`${baseUrl.value}/benevoles`, {
        onSuccess: () => modalBenevoleOuvert.value = false,
        preserveScroll: true,
    })
}

const supprimerBenevole = (b) => {
    if (confirm(`Retirer ${b.prenom} ${b.nom} de l'équipe bénévole ?`)) {
        router.delete(`${baseUrl.value}/benevoles/${b.id}`)
    }
}

const couleurStatutDotation = (statut) => ({
    distribue: { bg: 'bg-emerald-50', text: 'text-emerald-700', label: 'Distribué' },
    retourne:  { bg: 'bg-blue-50', text: 'text-blue-700', label: 'Retourné' },
    perdu:     { bg: 'bg-red-50', text: 'text-red-700', label: 'Perdu' },
}[statut] || { bg: 'bg-slate-100', text: 'text-slate-600', label: statut })

const initiales = (b) => `${b.prenom?.[0] ?? ''}${b.nom?.[0] ?? ''}`.toUpperCase()
</script>

<template>
    <DashboardLayout>

        <!-- ── EN-TÊTE ── -->
        <div class="mb-6">
            <Link :href="`/evenements/${evenement.id}`"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-text-sub hover:text-moov-blue">
                ← Retour à l'événement
            </Link>

            <div class="mt-4">
                <h1 class="font-display text-3xl font-extrabold text-text-main">Logistique</h1>
                <p class="mt-1 text-sm text-text-sub">
                    {{ evenement.titre }}
                    <span v-if="evenement.lieu"> · {{ evenement.lieu.nom }}</span>
                </p>
            </div>
        </div>

        <!-- ── KPIs ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Ressources</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">
                    {{ ressources?.length ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Dotations</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-orange-600">
                    {{ dotations?.length ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bénévoles</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-amber-600">
                    {{ benevoles?.length ?? 0 }}
                </p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">À retourner</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-violet-600">
                    {{ dotations?.filter(d => d.a_retourner && d.statut === 'distribue').length ?? 0 }}
                </p>
            </div>
        </div>

        <!-- ── ONGLETS ── -->
        <div class="mb-6 border-b border-border-soft">
            <nav class="flex gap-1">
                <button v-for="o in onglets" :key="o.value"
                        @click="ongletActif = o.value"
                        :class="['rounded-t-lg px-5 py-3 text-sm font-bold transition',
                            ongletActif === o.value
                                ? 'bg-card text-moov-blue border-2 border-b-0 border-border-soft'
                                : 'text-text-sub hover:text-moov-blue']">
                    {{ o.label }}
                </button>
            </nav>
        </div>

        
        <div v-show="ongletActif === 'ressources'" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-text-main">Inventaire matériel</h2>
                <button @click="ouvrirModalRessource"
                        class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    + Nouvelle Ressource
                </button>
            </div>

            <div v-if="ressources?.length" class="overflow-hidden rounded-xl bg-card shadow-card">
                <table class="min-w-full divide-y divide-border-soft text-sm">
                    <thead class="bg-page-bg/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Ressource</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">Quantité</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Coût total</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Fournisseur</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-soft bg-card">
                        <tr v-for="r in ressources" :key="r.id" class="transition hover:bg-page-bg/50">
                            <td class="px-4 py-3">
                                <p class="font-bold text-text-main">{{ r.nom }}</p>
                                <p v-if="r.observations" class="text-xs text-text-sub">{{ r.observations }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-blue-50 px-2 py-0.5 text-xs font-bold uppercase text-blue-700">
                                    {{ r.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-display text-lg font-extrabold text-moov-blue">
                                    {{ r.quantite_totale }}
                                </span>
                                <span v-if="r.unite" class="ml-1 text-xs text-text-sub">{{ r.unite }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-text-main">
                                {{ Number(r.cout_unitaire * r.quantite_totale).toLocaleString('fr-FR') }}
                                <span class="text-xs text-text-sub">FCFA</span>
                            </td>
                            <td class="px-4 py-3 text-text-sub">{{ r.fournisseur ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="supprimerRessource(r)"
                                        class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucune ressource enregistrée</p>
                <p class="mt-1 text-sm text-text-sub">Commencez par lister votre matériel</p>
            </div>
        </div>

        <div v-show="ongletActif === 'dotations'" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-text-main">Dotations distribuées</h2>
                <button @click="ouvrirModalDotation"
                        class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    + Nouvelle Dotation
                </button>
            </div>

            <div v-if="dotations?.length" class="overflow-hidden rounded-xl bg-card shadow-card">
                <table class="min-w-full divide-y divide-border-soft text-sm">
                    <thead class="bg-page-bg/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Bénéficiaire</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Item</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-text-sub">Qté</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Date remise</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-soft bg-card">
                        <tr v-for="d in dotations" :key="d.id" class="transition hover:bg-page-bg/50">
                            <td class="px-4 py-3 font-bold text-text-main">{{ d.beneficiaire }}</td>
                            <td class="px-4 py-3">
                                <p class="text-text-main">{{ d.item }}</p>
                                <p v-if="d.taille" class="text-xs text-text-sub">Taille : {{ d.taille }}</p>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-moov-blue">{{ d.quantite }}</td>
                            <td class="px-4 py-3 text-text-sub">{{ formaterDate(d.date_remise) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span :class="['inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-bold',
                                        couleurStatutDotation(d.statut).bg, couleurStatutDotation(d.statut).text]">
                                        {{ couleurStatutDotation(d.statut).label }}
                                    </span>
                                    <span v-if="d.a_retourner && d.statut === 'distribue'"
                                          class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700 w-fit">
                                        À retourner
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <button v-if="d.a_retourner && d.statut === 'distribue'"
                                            @click="marquerRetour(d)"
                                            class="rounded p-1.5 text-text-sub transition hover:bg-blue-50 hover:text-blue-600"
                                            title="Marquer comme retourné">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </button>
                                    <button @click="supprimerDotation(d)"
                                            class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucune dotation enregistrée</p>
                <p class="mt-1 text-sm text-text-sub">Distribuez du matériel aux participants</p>
            </div>
        </div>

      
        <div v-show="ongletActif === 'benevoles'" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-text-main">Équipe bénévole</h2>
                <button @click="ouvrirModalBenevole"
                        class="rounded-lg bg-moov-noir px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                    + Nouveau Bénévole
                </button>
            </div>

            <div v-if="benevoles?.length" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="b in benevoles" :key="b.id"
                     class="rounded-xl bg-card p-5 shadow-card transition hover:shadow-card-hover">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">
                                {{ initiales(b) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-text-main">{{ b.prenom }} {{ b.nom }}</p>
                                <p v-if="b.email" class="text-xs text-text-sub">{{ b.email }}</p>
                                <p v-if="b.telephone" class="text-xs text-text-sub">{{ b.telephone }}</p>
                                <span v-if="b.poste_affecte"
                                      class="mt-2 inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                                    {{ b.poste_affecte }}
                                </span>
                                <p v-if="b.horaires" class="mt-1 text-xs text-text-sub">
                                    Horaires : <span class="font-bold">{{ b.horaires }}</span>
                                </p>
                            </div>
                        </div>
                        <button @click="supprimerBenevole(b)"
                                class="rounded p-1.5 text-text-muted transition hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border-2 border-dashed border-border-soft py-12 text-center">
                <p class="font-bold text-text-main">Aucun bénévole enregistré</p>
                <p class="mt-1 text-sm text-text-sub">Ajoutez les bénévoles qui vous accompagnent</p>
            </div>
        </div>

        <div v-if="modalRessourceOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalRessourceOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouvelle ressource</h3>
                </div>
                <form @submit.prevent="creerRessource" class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom *</label>
                            <input v-model="formRessource.nom" type="text" required placeholder="Tables, chaises, sono..."
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Type</label>
                            <select v-model="formRessource.type"
                                    class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10">
                                <option value="materiel">Matériel</option>
                                <option value="mobilier">Mobilier</option>
                                <option value="technique">Technique</option>
                                <option value="alimentaire">Alimentaire</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Quantité *</label>
                            <input v-model.number="formRessource.quantite_totale" type="number" min="1" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Unité</label>
                            <input v-model="formRessource.unite" type="text" placeholder="pcs, kg, L..."
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Coût/unité</label>
                            <input v-model.number="formRessource.cout_unitaire" type="number" min="0"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Fournisseur</label>
                        <input v-model="formRessource.fournisseur" type="text"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalRessourceOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerRessource" :disabled="formRessource.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Ajouter
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════ MODALE DOTATION ════════ -->
        <div v-if="modalDotationOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalDotationOuvert = false">
            <div class="w-full max-w-md rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouvelle dotation</h3>
                </div>
                <form @submit.prevent="creerDotation" class="space-y-4 p-5">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Bénéficiaire *</label>
                        <input v-model="formDotation.beneficiaire" type="text" required placeholder="Nom du destinataire"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Item *</label>
                        <input v-model="formDotation.item" type="text" required placeholder="T-shirt, badge, kit..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Quantité *</label>
                            <input v-model.number="formDotation.quantite" type="number" min="1" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Taille</label>
                            <input v-model="formDotation.taille" type="text" placeholder="M, L, XL..."
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="formDotation.a_retourner"
                               class="h-4 w-4 rounded border-border-soft text-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                        <span class="text-sm text-text-main">À retourner après l'événement</span>
                    </label>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalDotationOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerDotation" :disabled="formDotation.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Distribuer
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════ MODALE BÉNÉVOLE ════════ -->
        <div v-if="modalBenevoleOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalBenevoleOuvert = false">
            <div class="w-full max-w-lg rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">Nouveau bénévole</h3>
                </div>
                <form @submit.prevent="creerBenevole" class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Nom *</label>
                            <input v-model="formBenevole.nom" type="text" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Prénom *</label>
                            <input v-model="formBenevole.prenom" type="text" required
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Email</label>
                            <input v-model="formBenevole.email" type="email"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Téléphone</label>
                            <input v-model="formBenevole.telephone" type="tel" placeholder="+226 70 12 34 56"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Poste affecté</label>
                        <input v-model="formBenevole.poste_affecte" type="text" placeholder="Accueil, Logistique, Sécurité..."
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">Horaires</label>
                        <input v-model="formBenevole.horaires" type="text" placeholder="08h - 17h"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                    </div>
                </form>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalBenevoleOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerBenevole" :disabled="formBenevole.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        Ajouter à l'équipe
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>