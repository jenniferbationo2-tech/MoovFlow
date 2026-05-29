<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    inscription: { type: Object, required: true },
    evenement:   { type: Object, required: true },
    typeCode:    { type: String, required: true },
})

// Token depuis l'URL (pour accès via email)
const urlParams = new URLSearchParams(window.location.search)
const token = urlParams.get('token')

const form = useForm({
    token: token,

    // Commun
    motivation: props.inscription.motivation || '',

    // BARA_MOUSSO
    localite:           props.inscription.localite || '',
    domaine_activite:   props.inscription.domaine_activite || '',
    effectif_employe:   props.inscription.effectif_employe || '',
    annee_creation:     props.inscription.annee_creation || null,
    url_video_pitch:    props.inscription.url_video_pitch || '',
    besoins_financiers: props.inscription.besoins_financiers || null,
    description_projet: props.inscription.description_projet || '',

    // SPORT
    nom_equipe:        props.inscription.nom_equipe || '',
    capitaine:         props.inscription.capitaine || '',
    categorie_age:     props.inscription.categorie_age || '',
    effectif_equipe:   props.inscription.effectif_equipe || null,
    couleurs_maillot:  props.inscription.couleurs_maillot || '',
    coach_nom:         props.inscription.coach_nom || '',
    joueurs_licencies: props.inscription.joueurs_licencies || null,

    // HACK
    niveau_equipe:     props.inscription.niveau_equipe || '',
    technologies:      props.inscription.technologies || '',
    presence_complete: props.inscription.presence_complete || true,
    url_portfolio:     props.inscription.url_portfolio || '',
    idee:              props.inscription.idee || '',

    // CHALLENGE
    titre_idee:            props.inscription.titre_idee || '',
    description:           props.inscription.description || '',
    stade_maturite:        props.inscription.stade_maturite || '',
    marche_vise:           props.inscription.marche_vise || '',
    investissement_requis: props.inscription.investissement_requis || null,
    statut_juridique:      props.inscription.statut_juridique || '',
})

const submit = () => {
    form.post(`/inscriptions/${props.inscription.id}/dossier-complet`, {
        preserveScroll: true,
    })
}

const couleurType = computed(() => ({
    BARA_MOUSSO: 'rose',
    SPORT:       'cyan',
    HACK:        'orange',
    CHALLENGE:   'violet',
}[props.typeCode] || 'blue'))
</script>

<template>
    <PublicLayout>
        <section class="bg-page-bg py-10">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                <!-- En-tête -->
                <div class="mb-6">
                    <Link :href="`/inscriptions/${inscription.id}`"
                          class="mb-4 inline-flex items-center gap-2 text-sm font-bold text-text-sub transition hover:text-moov-blue">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour
                    </Link>

                    <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700">
                         Étape 2 / 2
                    </span>
                    <h1 class="mt-3 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                        Compléter votre dossier
                    </h1>
                    <p class="mt-2 text-sm text-text-sub">
                        Pour <strong>{{ evenement.titre }}</strong>
                    </p>
                </div>

                <!-- Info contextuelle -->
                <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="font-bold text-blue-900">Vous êtes présélectionné(e) !</p>
                            <p class="mt-1 text-blue-800">
                                Complétez les informations ci-dessous pour finaliser votre candidature.
                                Notre équipe procèdera à l'analyse finale.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FORMULAIRE PAR TYPE -->
                <form @submit.prevent="submit" class="space-y-5">

                    <!-- ════════ BARA MOUSSO ════════ -->
                    <div v-if="typeCode === 'BARA_MOUSSO'" class="space-y-5 rounded-2xl bg-white p-6 shadow-card">
                        <h2 class="font-display text-lg font-extrabold text-text-main">
                            Informations sur votre projet
                        </h2>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Localité (Province/Région) *
                            </label>
                            <input v-model="form.localite" type="text" required
                                   placeholder="Ex: Ouagadougou, Bobo-Dioulasso..."
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Domaine d'activité *
                                </label>
                                <select v-model="form.domaine_activite" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="Agriculture">Agriculture & élevage</option>
                                    <option value="Artisanat">Artisanat</option>
                                    <option value="Commerce">Commerce</option>
                                    <option value="Restauration">Restauration</option>
                                    <option value="Mode">Mode & beauté</option>
                                    <option value="Education">Éducation</option>
                                    <option value="Sante">Santé</option>
                                    <option value="Numerique">Services numériques</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Effectif employé *
                                </label>
                                <select v-model="form.effectif_employe" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="solo">Solo</option>
                                    <option value="2-5">2 à 5 personnes</option>
                                    <option value="6-10">6 à 10 personnes</option>
                                    <option value="+10">Plus de 10 personnes</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Année de création
                                </label>
                                <input v-model.number="form.annee_creation" type="text" inputmode="numeric"
                                       @input="form.annee_creation = $event.target.value.replace(/\D/g, '')"
                                       placeholder="Ex: 2020"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Besoins financiers (FCFA)
                                </label>
                                <input v-model="form.besoins_financiers" type="text" inputmode="numeric"
                                       @input="form.besoins_financiers = $event.target.value.replace(/\D/g, '')"
                                       placeholder="Ex: 500000"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                URL vidéo de pitch (optionnel)
                            </label>
                            <input v-model="form.url_video_pitch" type="url"
                                   placeholder="https://youtube.com/..."
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Description du projet * (min. 50 caractères)
                            </label>
                            <textarea v-model="form.description_projet" rows="6" required
                                      placeholder="Présentez votre projet, vos objectifs, votre impact..."
                                      class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                            <p class="mt-1 text-xs text-text-muted">
                                {{ form.description_projet.length }} / 3000 caractères
                            </p>
                        </div>
                    </div>

                    <!-- ════════ SPORT ════════ -->
                    <div v-else-if="typeCode === 'SPORT'" class="space-y-5 rounded-2xl bg-white p-6 shadow-card">
                        <h2 class="font-display text-lg font-extrabold text-text-main">
                            Composition de votre équipe
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Nom de l'équipe *
                                </label>
                                <input v-model="form.nom_equipe" type="text" required
                                       placeholder="Les Lions de Ouagadougou"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Capitaine *
                                </label>
                                <input v-model="form.capitaine" type="text" required
                                       placeholder="Nom du capitaine"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Catégorie d'âge *
                                </label>
                                <select v-model="form.categorie_age" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="U17">U17</option>
                                    <option value="U20">U20</option>
                                    <option value="Senior">Senior</option>
                                    <option value="Veteran">Vétéran</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Effectif total *
                                </label>
                                <input v-model.number="form.effectif_equipe" type="text" inputmode="numeric" required
                                       @input="form.effectif_equipe = $event.target.value.replace(/\D/g, '')"
                                       placeholder="Ex: 15"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Couleurs maillot
                                </label>
                                <input v-model="form.couleurs_maillot" type="text"
                                       placeholder="Ex: Bleu et blanc"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Coach / Encadrant
                                </label>
                                <input v-model="form.coach_nom" type="text"
                                       placeholder="Nom du coach"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Nombre de joueurs licenciés
                            </label>
                            <input v-model="form.joueurs_licencies" type="text" inputmode="numeric"
                                   @input="form.joueurs_licencies = $event.target.value.replace(/\D/g, '')"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>
                    </div>

                    <!-- ════════ HACK ════════ -->
                    <div v-else-if="typeCode === 'HACK'" class="space-y-5 rounded-2xl bg-white p-6 shadow-card">
                        <h2 class="font-display text-lg font-extrabold text-text-main">
                            Votre équipe et votre projet
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Nom de l'équipe *
                                </label>
                                <input v-model="form.nom_equipe" type="text" required
                                       placeholder="Code Warriors"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Niveau de l'équipe *
                                </label>
                                <select v-model="form.niveau_equipe" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="Debutant">Débutant</option>
                                    <option value="Intermediaire">Intermédiaire</option>
                                    <option value="Avance">Avancé / Expert</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Technologies maîtrisées
                            </label>
                            <textarea v-model="form.technologies" rows="2"
                                      placeholder="Ex: Python, JavaScript, React, MySQL, IA..."
                                      class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                URL portfolio / GitHub (optionnel)
                            </label>
                            <input v-model="form.url_portfolio" type="url"
                                   placeholder="https://github.com/..."
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Idée / Pitch * (min. 30 caractères)
                            </label>
                            <textarea v-model="form.idee" rows="5" required
                                      placeholder="Présentez l'idée que vous souhaitez développer..."
                                      class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                        </div>

                        <label class="flex items-start gap-3 rounded-lg border-2 border-border-soft p-4 cursor-pointer hover:bg-page-bg/50">
                            <input v-model="form.presence_complete" type="checkbox"
                                   class="mt-0.5 h-5 w-5 rounded border-border-soft"/>
                            <div>
                                <p class="text-sm font-bold text-text-main">Présence complète garantie</p>
                                <p class="text-xs text-text-sub">Je m'engage à participer aux heures complètes du hackathon</p>
                            </div>
                        </label>
                    </div>

                    <!-- ════════ CHALLENGE ════════ -->
                    <div v-else-if="typeCode === 'CHALLENGE'" class="space-y-5 rounded-2xl bg-white p-6 shadow-card">
                        <h2 class="font-display text-lg font-extrabold text-text-main">
                            Votre projet d'innovation
                        </h2>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Titre de l'idée *
                            </label>
                            <input v-model="form.titre_idee" type="text" required
                                   placeholder="Une description courte et percutante"
                                   class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Description détaillée * (min. 100 caractères)
                            </label>
                            <textarea v-model="form.description" rows="6" required
                                      placeholder="Décrivez votre projet, le problème résolu, l'innovation..."
                                      class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none transition focus:border-moov-blue"/>
                            <p class="mt-1 text-xs text-text-muted">
                                {{ form.description.length }} caractères
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Stade de maturité *
                                </label>
                                <select v-model="form.stade_maturite" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="Idee">Idée seulement</option>
                                    <option value="Prototype">Prototype</option>
                                    <option value="Lance">Déjà lancé</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Marché visé *
                                </label>
                                <select v-model="form.marche_vise" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="Local">Local</option>
                                    <option value="National">National</option>
                                    <option value="International">International</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Investissement requis (FCFA)
                                </label>
                                <input v-model="form.investissement_requis" type="text" inputmode="numeric"
                                       @input="form.investissement_requis = $event.target.value.replace(/\D/g, '')"
                                       placeholder="Ex: 1000000"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Statut juridique *
                                </label>
                                <select v-model="form.statut_juridique" required
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-moov-blue">
                                    <option value="">Sélectionner</option>
                                    <option value="Personne_physique">Personne physique</option>
                                    <option value="Association">Association</option>
                                    <option value="Entreprise_individuelle">Entreprise individuelle</option>
                                    <option value="SARL">SARL / SA</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de soumission -->
                    <button type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-emerald-600 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50">
                        <span v-if="form.processing">⏳ Envoi en cours...</span>
                        <span v-else>Soumettre mon dossier complet →</span>
                    </button>

                    <p class="text-center text-xs text-text-muted">
                        Vous recevrez un email de confirmation de réception du dossier.
                    </p>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>