<script setup>
import { computed } from 'vue'

const props = defineProps({
    form: { type: Object, required: true },
    type: { type: Object, required: true },
    lieux: { type: Array, required: true },
    isEdit: { type: Boolean, default: false },
    userRole: { type: Object, default: () => ({}) },
})

defineEmits(['precedent', 'valider'])

const estResponsable = computed(() =>
    props.userRole?.estResponsable ?? false
)

const nomLieu = computed(() => {
    const lieu = props.lieux.find(l => l.id === Number(props.form.lieu_id))
    return lieu?.nom ?? '—'
})

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

const formaterMontant = (m) => {
    if (!m) return '—'
    return Number(m).toLocaleString('fr-FR') + ' FCFA'
}
</script>

<template>
    <div class="rounded-2xl bg-white shadow-card">

        <div class="border-b border-border-soft p-6">
            <h2 class="font-display text-2xl font-extrabold text-text-main">
                Récapitulatif final
            </h2>
            <p class="mt-1 text-sm text-text-sub">
                Vérifiez toutes les informations avant de créer l'événement
            </p>
        </div>

        <div class="space-y-6 p-6">

            <!-- Type -->
            <div class="rounded-xl bg-page-bg/50 p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Type d'événement</p>
                <p class="mt-1 font-display text-xl font-extrabold text-moov-blue">{{ type.nom }}</p>
            </div>

            <!-- Étape 1 -->
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-moov-blue">
                    1 Informations
                </p>
                <div class="space-y-2 rounded-xl border border-border-soft bg-white p-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-text-sub">Titre</span>
                        <strong class="text-text-main">{{ form.titre || '—' }}</strong>
                    </div>
                    <div v-if="form.description" class="text-sm">
                        <p class="text-text-sub">Description</p>
                        <p class="mt-1 text-text-main">{{ form.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Étape 2 -->
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-moov-blue">
                    2 Lieu & Dates
                </p>
                <div class="space-y-2 rounded-xl border border-border-soft bg-white p-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-text-sub">Lieu</span>
                        <strong class="text-text-main">{{ nomLieu }}</strong>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-sub">Début</span>
                        <strong class="text-text-main">{{ formaterDate(form.date_debut) }}</strong>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-sub">Fin</span>
                        <strong class="text-text-main">{{ formaterDate(form.date_fin) }}</strong>
                    </div>
                    <div v-if="form.capacite_max" class="flex justify-between text-sm">
                        <span class="text-text-sub">Capacité max</span>
                        <strong class="text-text-main">{{ form.capacite_max }} personnes</strong>
                    </div>
                </div>
            </div>

            <!-- Étape 3 -->
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-moov-blue">
                    3 Budget & RSE
                </p>
                <div class="space-y-2 rounded-xl border border-border-soft bg-white p-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-text-sub">Budget prévisionnel</span>
                        <strong class="text-text-main">{{ formaterMontant(form.budget_previsionnel) }}</strong>
                    </div>
                    <div v-if="form.tarifs.length" class="text-sm">
                        <p class="text-text-sub">Tarifs ({{ form.tarifs.length }})</p>
                        <ul class="mt-1 space-y-1">
                            <li v-for="(t, i) in form.tarifs" :key="i" class="flex justify-between">
                                <span class="text-text-main">{{ t.nom }}</span>
                                <strong class="text-moov-blue">{{ formaterMontant(t.montant) }}</strong>
                            </li>
                        </ul>
                    </div>
                    <div v-if="form.public_cible" class="flex justify-between text-sm">
                        <span class="text-text-sub">Public cible</span>
                        <strong class="text-text-main">{{ form.public_cible }}</strong>
                    </div>
                    <div v-if="form.cible_beneficiaires" class="flex justify-between text-sm">
                        <span class="text-text-sub">Cible bénéficiaires</span>
                        <strong class="text-text-main">{{ form.cible_beneficiaires }}</strong>
                    </div>
                </div>
            </div>

            <!-- Étape 4 - varie par type, on affiche les principales infos -->
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-moov-blue">
                    4 Configuration {{ type.nom }}
                </p>
                <div class="rounded-xl border border-border-soft bg-white p-5 text-sm text-text-sub">

                    <template v-if="type.code === 'BARA_MOUSSO'">
                        <p v-if="form.domaines_acceptes?.length"><strong>Domaines :</strong> {{
                            form.domaines_acceptes.join(', ') }}</p>
                        <p v-if="form.dotation_principale"><strong>Dotation :</strong> {{
                            formaterMontant(form.dotation_principale) }}</p>
                        <p v-if="form.nombre_laureates"><strong>Lauréates :</strong> {{ form.nombre_laureates }}</p>
                    </template>

                    <template v-else-if="type.code === 'CONF'">
                        <p v-if="form.theme_principal"><strong>Thème :</strong> {{ form.theme_principal }}</p>
                        <p v-if="form.profession_cible"><strong>Cible :</strong> {{ form.profession_cible }}</p>
                        <p><strong>Diffusion en ligne :</strong> {{ form.diffusion_en_ligne ? 'Oui' : 'Non' }}</p>
                    </template>

                    <template v-else-if="type.code === 'SPORT'">
                        <p v-if="form.discipline"><strong>Discipline :</strong> {{ form.discipline }}</p>
                        <p v-if="form.categorie_age"><strong>Catégorie :</strong> {{ form.categorie_age }}</p>
                        <p v-if="form.nombre_max_equipes"><strong>Équipes max :</strong> {{ form.nombre_max_equipes }}
                        </p>
                    </template>

                    <template v-else-if="type.code === 'CHALLENGE'">
                        <p v-if="form.thematique_challenge"><strong>Thématique :</strong> {{ form.thematique_challenge
                        }}</p>
                        <p v-if="form.dotation_totale"><strong>Dotation :</strong> {{
                            formaterMontant(form.dotation_totale) }}</p>
                        <p v-if="form.stades_acceptes?.length"><strong>Stades :</strong> {{ form.stades_acceptes.join }}</p>
                    </template>

                    <template v-else-if="type.code === 'FORMATION'">
                        <p v-if="form.domaine_formation"><strong>Domaine :</strong> {{ form.domaine_formation }}</p>
                        <p v-if="form.niveau_requis"><strong>Niveau :</strong> {{ form.niveau_requis }}</p>
                        <p v-if="form.duree_heures"><strong>Durée :</strong> {{ form.duree_heures }}h</p>
                        <p><strong>Certification :</strong> {{ form.certification ? 'Oui' : 'Non' }}</p>
                    </template>

                    <template v-else-if="type.code === 'HACK'">
                        <p v-if="form.theme_hackathon"><strong>Thème :</strong> {{ form.theme_hackathon }}</p>
                        <p v-if="form.duree_heures_hack"><strong>Durée :</strong> {{ form.duree_heures_hack }}h</p>
                        <p v-if="form.technologies_suggerees?.length"><strong>Tech :</strong> {{
                            form.technologies_suggerees.join(', ') }}</p>
                    </template>

                    <template v-else-if="type.code === 'SALON'">
                        <p v-if="form.nom_salon_hote"><strong>Salon hôte :</strong> {{ form.nom_salon_hote }}</p>
                        <p v-if="form.lieu_stand"><strong>Stand :</strong> {{ form.lieu_stand }}</p>
                        <p v-if="form.superficie_stand"><strong>Superficie :</strong> {{ form.superficie_stand }} m²</p>
                    </template>
                </div>
            </div>

            <!-- Info statut -->
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- Message selon le rôle -->
                    <div v-if="estResponsable" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-emerald-800">
                                En tant que responsable dCIRP, votre événement sera
                                <strong>publié directement</strong> sans validation supplémentaire.
                            </p>
                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-amber-800">
                                L'événement sera créé en <strong>Brouillon</strong>.
                                Demandez la validation au responsable dCIRP pour publication.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer avec bouton final -->
        <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
            <button @click="$emit('precedent')"
                class="flex items-center gap-2 rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour
            </button>

            <button @click="$emit('valider')" :disabled="form.processing"
                class="flex items-center gap-2 rounded-lg bg-emerald-600 px-8 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50">
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                {{ form.processing ? 'Création...' : (isEdit ? 'Mettre à jour' : 'Créer l\'événement') }}
            </button>
        </div>
    </div>
</template>