<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const props = defineProps({
    evenement: Object,
})

const typeCode = computed(() => props.evenement.type_evenement?.code)

const form = useForm({
    evenement_id: props.evenement.id,
    tarif_id:     props.evenement.tarifs?.[0]?.id ?? null,
    reglement_accepte: false,

    organisation:  '',
    fonction:      '',
    motivation:    '',
    fichier_joint: null,

    nom_association:        '',
    description_projet:     '',
    nb_membres_association: '',
    budget_projet:          '',

    nom_equipe:         '',
    nb_joueurs:         '',
    categorie_equipe:   '',
    responsable_equipe: '',

    competences_techniques: '',
    stack_technologique:    '',
    nom_equipe_hack:        '',
    nb_membres_equipe:      '',

    niveau_formation:        '',
    objectifs_apprentissage: '',

    secteur_activite:  '',
    type_visite_salon: '',
    interets_b2b:      '',

    titre_idee:   '',
    secteur_idee: '',
})

const submit = () => {
    form.post('/inscriptions', { forceFormData: true })
}

const titreFormulaire = computed(() => ({
    BARA_MOUSSO: 'Dossier de candidature — Bara Mousso',
    CONF:        'Inscription — Conférence',
    SPORT:       'Inscription équipe — Tournoi sportif',
    CHALLENGE:   'Dépôt d\'idée — Challenge Innovation',
    FORMATION:   'Inscription — Formation numérique',
    HACK:        'Inscription équipe — Hackathon',
    SALON:       'Inscription — Salon',
    ATELIER:     'Inscription — Atelier',
    WEBINAIRE:   'Inscription — Webinaire',
}[typeCode.value] || 'Formulaire d\'inscription'))

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric'
    })
}

const tarifSelectionne = computed(() =>
    props.evenement.tarifs?.find(t => t.id === form.tarif_id)
)

const estPayant = computed(() => (tarifSelectionne.value?.montant ?? 0) > 0)

const peutSoumettre = computed(() => {
    if (form.processing) return false
    if (props.evenement.reglement_pdf_url && !form.reglement_accepte) return false
    return true
})
</script>

<template>
    <PublicLayout>
        <div class="mx-auto max-w-3xl px-4 py-10">

            <!-- Fil d'ariane -->
            <Link :href="`/evenements/${evenement.id}`"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-text-sub hover:text-moov-blue">
                ← Retour à l'événement
            </Link>

            <!-- En-tête -->
            <div class="mt-6 mb-8">
                <h1 class="font-display text-3xl font-extrabold text-text-main">
                    {{ titreFormulaire }}
                </h1>
                <p class="mt-2 text-base text-text-sub">
                    {{ evenement.titre }}
                    <span v-if="evenement.lieu"> · {{ evenement.lieu.nom }}</span>
                    <span v-if="evenement.date_debut"> · {{ formaterDate(evenement.date_debut) }}</span>
                </p>
            </div>

            <!-- 🆕 BLOC RÈGLEMENT (en haut, obligatoire) -->
            <div v-if="evenement.reglement_pdf_url"
                 class="mb-6 rounded-xl border-2 border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-amber-900">Règlement de l'événement</h3>
                        <p class="mt-1 text-sm text-amber-800">
                            Avant de soumettre votre dossier, veuillez prendre connaissance des règles et consignes spécifiques à cet événement.
                        </p>
                        <a :href="evenement.reglement_pdf_url"
                           target="_blank"
                           download
                           class="mt-3 inline-flex items-center gap-2 rounded-lg border border-amber-400 bg-white px-4 py-2 text-sm font-bold text-amber-900 transition hover:bg-amber-100">
                            📄 Télécharger le règlement PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bandeau info préinscription -->
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-blue-900">Processus de préinscription</p>
                        <p class="mt-1 text-sm text-blue-800">
                            Votre dossier sera analysé par l'organisateur. Vous serez notifié(e) par email
                            une fois la décision prise (acceptation ou refus motivé).
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- ════════ CHAMPS COMMUNS ════════ -->
                <div class="rounded-xl bg-card p-6 shadow-card">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Vos informations</h2>
                    <p class="mb-5 text-sm text-text-sub">Renseignements généraux</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Organisation / Entreprise
                            </label>
                            <input v-model="form.organisation" type="text"
                                   placeholder="Ex: Moov Africa, ONG XYZ..."
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Fonction / Poste
                            </label>
                            <input v-model="form.fonction" type="text"
                                   placeholder="Ex: Étudiant, Manager..."
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Motivation
                                <span class="text-text-muted normal-case">(optionnel)</span>
                            </label>
                            <textarea v-model="form.motivation" rows="3"
                                      placeholder="Pourquoi souhaitez-vous participer ?"
                                      class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Document joint <span class="text-text-muted normal-case">(PDF/DOC, max 5MB)</span>
                            </label>
                            <input type="file" accept=".pdf,.doc,.docx"
                                   @change="form.fichier_joint = $event.target.files[0]"
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-moov-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-moov-blue hover:file:bg-moov-blue/10"/>
                        </div>
                    </div>
                </div>

                <!-- BARA MOUSSO -->
                <div v-if="typeCode === 'BARA_MOUSSO'"
                     class="rounded-xl border-l-4 border-amber-500 bg-amber-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre association et projet</h2>
                    <p class="mb-5 text-sm text-text-sub">Informations sur l'association portant le projet</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Nom de l'association *
                            </label>
                            <input v-model="form.nom_association" type="text" required
                                   placeholder="Ex: Femmes Entrepreneures du Burkina"
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Description du projet *
                            </label>
                            <textarea v-model="form.description_projet" rows="4" required
                                      placeholder="Décrivez votre projet en détail"
                                      class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"/>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Nombre de membres *
                                </label>
                                <input v-model="form.nb_membres_association" type="number" min="1" required
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Budget du projet (FCFA) *
                                </label>
                                <input v-model="form.budget_projet" type="number" min="0" required
                                       placeholder="500000"
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SPORT -->
                <div v-if="typeCode === 'SPORT'"
                     class="rounded-xl border-l-4 border-blue-500 bg-blue-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre équipe</h2>
                    <p class="mb-5 text-sm text-text-sub">Informations sur l'équipe participante</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Nom de l'équipe *
                            </label>
                            <input v-model="form.nom_equipe" type="text" required
                                   placeholder="Ex: Les Lions de Ouaga"
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"/>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Joueurs *
                                </label>
                                <input v-model="form.nb_joueurs" type="number" min="1" required placeholder="11"
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Catégorie *
                                </label>
                                <select v-model="form.categorie_equipe" required
                                        class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                    <option value="">Sélectionner</option>
                                    <option value="junior">Junior</option>
                                    <option value="senior">Senior</option>
                                    <option value="mixte">Mixte</option>
                                    <option value="feminin">Féminin</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Responsable *
                                </label>
                                <input v-model="form.responsable_equipe" type="text" required placeholder="Capitaine"
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HACKATHON -->
                <div v-if="typeCode === 'HACK'"
                     class="rounded-xl border-l-4 border-orange-500 bg-orange-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre équipe Hackathon</h2>
                    <p class="mb-5 text-sm text-text-sub">Informations techniques sur votre équipe</p>

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Nom de l'équipe *
                                </label>
                                <input v-model="form.nom_equipe_hack" type="text" required
                                       placeholder="Ex: TeamCode Burkina"
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Membres *
                                </label>
                                <input v-model="form.nb_membres_equipe" type="number" min="1" max="6" required placeholder="4"
                                       class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10"/>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Compétences techniques *
                            </label>
                            <textarea v-model="form.competences_techniques" rows="2" required
                                      placeholder="Ex: Développement web, IA, mobile..."
                                      class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Stack technologique *
                            </label>
                            <input v-model="form.stack_technologique" type="text" required
                                   placeholder="Ex: Laravel, Vue.js, MySQL"
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/10"/>
                        </div>
                    </div>
                </div>

                <!-- FORMATION -->
                <div v-if="typeCode === 'FORMATION'"
                     class="rounded-xl border-l-4 border-emerald-500 bg-emerald-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre profil et objectifs</h2>
                    <p class="mb-5 text-sm text-text-sub">Aidez-nous à adapter la formation</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Niveau actuel *
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label v-for="n in [
                                    { value: 'debutant', label: 'Débutant', desc: 'Aucune base' },
                                    { value: 'intermediaire', label: 'Intermédiaire', desc: 'Quelques bases' },
                                    { value: 'avance', label: 'Avancé', desc: 'Bonne maîtrise' },
                                ]" :key="n.value"
                                :class="['cursor-pointer rounded-lg border-2 p-3 text-center transition',
                                    form.niveau_formation === n.value
                                        ? 'border-emerald-500 bg-emerald-50'
                                        : 'border-border-soft bg-white hover:border-emerald-300']">
                                    <input type="radio" v-model="form.niveau_formation" :value="n.value" class="sr-only"/>
                                    <p class="font-bold text-text-main">{{ n.label }}</p>
                                    <p class="mt-1 text-xs text-text-sub">{{ n.desc }}</p>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Objectifs d'apprentissage *
                            </label>
                            <textarea v-model="form.objectifs_apprentissage" rows="3" required
                                      placeholder="Que souhaitez-vous apprendre ?"
                                      class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10"/>
                        </div>
                    </div>
                </div>

                <!-- SALON -->
                <div v-if="typeCode === 'SALON'"
                     class="rounded-xl border-l-4 border-indigo-500 bg-indigo-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre profil professionnel</h2>
                    <p class="mb-5 text-sm text-text-sub">Pour mieux vous accueillir sur le stand Moov</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Secteur d'activité *
                            </label>
                            <input v-model="form.secteur_activite" type="text" required
                                   placeholder="Ex: Artisanat, Commerce, Agriculture..."
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Type de visite *
                            </label>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <label v-for="t in [
                                    { value: 'visiteur', label: 'Visiteur', desc: 'Découvrir Moov' },
                                    { value: 'partenaire_potentiel', label: 'Partenaire', desc: 'Collaboration B2B' },
                                    { value: 'client_potentiel', label: 'Client', desc: 'Solutions Moov' },
                                ]" :key="t.value"
                                :class="['cursor-pointer rounded-lg border-2 p-3 text-center transition',
                                    form.type_visite_salon === t.value
                                        ? 'border-indigo-500 bg-indigo-50'
                                        : 'border-border-soft bg-white hover:border-indigo-300']">
                                    <input type="radio" v-model="form.type_visite_salon" :value="t.value" class="sr-only"/>
                                    <p class="font-bold text-text-main">{{ t.label }}</p>
                                    <p class="mt-1 text-xs text-text-sub">{{ t.desc }}</p>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Centres d'intérêt B2B
                                <span class="text-text-muted normal-case">(optionnel)</span>
                            </label>
                            <textarea v-model="form.interets_b2b" rows="2"
                                      placeholder="Ex: Solutions de paiement mobile..."
                                      class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"/>
                        </div>
                    </div>
                </div>

                <!-- CHALLENGE -->
                <div v-if="typeCode === 'CHALLENGE'"
                     class="rounded-xl border-l-4 border-violet-500 bg-violet-50/50 p-6">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Votre idée innovante</h2>
                    <p class="mb-5 text-sm text-text-sub">Présentez votre projet</p>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Titre de votre idée *
                            </label>
                            <input v-model="form.titre_idee" type="text" required
                                   placeholder="Ex: Application de microfinance pour zones rurales"
                                   class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/10"/>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Secteur *
                            </label>
                            <select v-model="form.secteur_idee" required
                                    class="w-full rounded-lg border border-border-soft px-4 py-2.5 text-sm outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/10">
                                <option value="">Sélectionner un secteur</option>
                                <option value="finance">Finance / Fintech</option>
                                <option value="education">Éducation</option>
                                <option value="sante">Santé</option>
                                <option value="agriculture">Agriculture</option>
                                <option value="energie">Énergie</option>
                                <option value="commerce">Commerce / E-commerce</option>
                                <option value="transport">Transport / Mobilité</option>
                                <option value="environnement">Environnement</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TARIFS -->
                <div v-if="evenement.tarifs?.length" class="rounded-xl bg-card p-6 shadow-card">
                    <h2 class="mb-1 font-display text-lg font-bold text-text-main">Tarification</h2>
                    <p class="mb-5 text-sm text-text-sub">Choisissez votre formule</p>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label v-for="t in evenement.tarifs" :key="t.id"
                               :class="['cursor-pointer rounded-lg border-2 p-4 transition',
                                   form.tarif_id === t.id
                                       ? 'border-moov-blue bg-moov-blue-50'
                                       : 'border-border-soft bg-white hover:border-moov-blue/30']">
                            <input type="radio" v-model="form.tarif_id" :value="t.id" class="sr-only"/>
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-bold text-text-main">{{ t.libelle }}</p>
                                    <p v-if="t.montant > 0" class="mt-1 font-display text-2xl font-extrabold text-moov-blue">
                                        {{ Number(t.montant).toLocaleString('fr-FR') }}
                                        <span class="text-sm font-bold">{{ t.devise }}</span>
                                    </p>
                                    <p v-else class="mt-1 font-display text-2xl font-extrabold text-emerald-600">
                                        Gratuit
                                    </p>
                                </div>
                                <div :class="['flex h-5 w-5 items-center justify-center rounded-full border-2',
                                    form.tarif_id === t.id ? 'border-moov-blue' : 'border-border-soft']">
                                    <div v-if="form.tarif_id === t.id" class="h-2.5 w-2.5 rounded-full bg-moov-blue"/>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 🆕 ACCEPTATION DU RÈGLEMENT -->
                <div v-if="evenement.reglement_pdf_url"
                     class="rounded-xl border-2 border-amber-200 bg-amber-50 p-5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.reglement_accepte" required
                               class="mt-1 h-5 w-5 rounded border-2 border-amber-400 text-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                        <span class="text-sm text-amber-900">
                            <strong>J'ai pris connaissance du règlement</strong> et j'accepte les conditions
                            de participation à l'événement <strong>{{ evenement.titre }}</strong>.
                            <a :href="evenement.reglement_pdf_url" target="_blank"
                               class="font-bold text-moov-orange underline hover:text-moov-orange-dark">
                                Relire le règlement
                            </a>
                        </span>
                    </label>
                    <p v-if="form.errors.reglement_accepte" class="mt-2 text-xs font-bold text-red-600">
                        {{ form.errors.reglement_accepte }}
                    </p>
                </div>

                <!-- ACTIONS -->
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-card p-6 shadow-card">
                    <div>
                        <p v-if="estPayant" class="text-sm text-text-sub">
                            Tarif sélectionné :
                            <span class="font-display text-base font-extrabold text-moov-blue">
                                {{ Number(tarifSelectionne?.montant ?? 0).toLocaleString('fr-FR') }} {{ tarifSelectionne?.devise }}
                            </span>
                        </p>
                        <p v-else class="text-sm font-bold text-emerald-600">
                            Inscription gratuite
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link :href="`/evenements/${evenement.id}`"
                              class="rounded-lg border border-border-soft px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                            Annuler
                        </Link>
                        <button type="submit"
                                :disabled="!peutSoumettre"
                                class="rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-40 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Envoi en cours...' : 'Soumettre mon dossier' }}
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </PublicLayout>
</template>