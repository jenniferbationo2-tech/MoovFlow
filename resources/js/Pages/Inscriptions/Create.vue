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
        <div class="min-h-screen bg-gradient-to-b from-slate-50 to-white py-10">
            <div class="mx-auto max-w-3xl px-4">

                <!-- ── Retour ── -->
                <Link :href="`/evenements/${evenement.id}`"
                      class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition hover:text-moov-blue">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour à l'événement
                </Link>

                <!-- ── En-tête ── -->
                <div class="mt-6 mb-8">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-moov-orange">
                        Pré-inscription
                    </p>
                    <h1 class="mt-2 font-display text-3xl font-extrabold text-slate-900 sm:text-4xl">
                        {{ titreFormulaire }}
                    </h1>

                    <!-- Récap événement -->
                    <div class="mt-4 inline-flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm text-slate-600 shadow-sm">
                        <span class="font-bold text-slate-900">{{ evenement.titre }}</span>
                        <span v-if="evenement.lieu" class="text-slate-300">·</span>
                        <span v-if="evenement.lieu" class="inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ evenement.lieu.nom }}
                        </span>
                        <span v-if="evenement.date_debut" class="text-slate-300">·</span>
                        <span v-if="evenement.date_debut" class="inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ formaterDate(evenement.date_debut) }}
                        </span>
                    </div>
                </div>

                <!-- ── Bandeau info (NOUVEAU - bleu sobre) ── -->
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-blue-50 border border-blue-200 p-4">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-blue-900">Processus de pré-inscription</p>
                        <p class="mt-0.5 text-sm text-blue-800">
                            Votre dossier sera analysé par l'organisateur. Vous serez notifié(e) par email
                            de la décision finale.
                        </p>
                    </div>
                </div>

                <!-- ── Règlement (si présent) — Couleur sobre maintenant ── -->
                <div v-if="evenement.reglement_pdf_url"
                     class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-moov-orange/10">
                            <svg class="h-5 w-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-display text-base font-bold text-slate-900">Règlement de l'événement</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Prenez connaissance des règles et consignes spécifiques avant de soumettre.
                            </p>
                            <a :href="evenement.reglement_pdf_url" target="_blank" download
                               class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-moov-orange hover:text-moov-orange">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Télécharger le règlement
                            </a>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- ════════ INFORMATIONS GÉNÉRALES ════════ -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-moov-blue/10">
                                <svg class="h-5 w-5 text-moov-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Vos informations</h2>
                                <p class="text-xs text-slate-500">Renseignements généraux</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Organisation / Entreprise
                                </label>
                                <input v-model="form.organisation" type="text"
                                       placeholder="Ex: Moov Africa, ONG XYZ..."
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Fonction / Poste
                                </label>
                                <input v-model="form.fonction" type="text"
                                       placeholder="Ex: Étudiant, Manager..."
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Motivation
                                    <span class="font-normal text-slate-400">— optionnel</span>
                                </label>
                                <textarea v-model="form.motivation" rows="3"
                                          placeholder="Pourquoi souhaitez-vous participer ?"
                                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Document joint
                                    <span class="font-normal text-slate-400">— PDF/DOC, max 5MB</span>
                                </label>
                                <input type="file" accept=".pdf,.doc,.docx"
                                       @change="form.fichier_joint = $event.target.files[0]"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-moov-blue/10 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-moov-blue hover:file:bg-moov-blue/20"/>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ BARA MOUSSO ════════ -->
                    <div v-if="typeCode === 'BARA_MOUSSO'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100">
                                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre association et projet</h2>
                                <p class="text-xs text-slate-500">Informations sur l'association porteuse</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Nom de l'association <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.nom_association" type="text" required
                                       placeholder="Ex: Femmes Entrepreneures du Burkina"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Description du projet <span class="text-red-500">*</span>
                                </label>
                                <textarea v-model="form.description_projet" rows="4" required
                                          placeholder="Décrivez votre projet en détail"
                                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"/>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Nombre de membres <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.nb_membres_association" type="number" min="1" required
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"/>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Budget du projet (FCFA) <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.budget_projet" type="number" min="0" required
                                           placeholder="500000"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SPORT ════════ -->
                    <div v-if="typeCode === 'SPORT'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre équipe</h2>
                                <p class="text-xs text-slate-500">Informations sur l'équipe participante</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Nom de l'équipe <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.nom_equipe" type="text" required
                                       placeholder="Ex: Les Lions de Ouaga"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"/>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Joueurs <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.nb_joueurs" type="number" min="1" required placeholder="11"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"/>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Catégorie <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="form.categorie_equipe" required
                                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        <option value="">Sélectionner</option>
                                        <option value="junior">Junior</option>
                                        <option value="senior">Senior</option>
                                        <option value="mixte">Mixte</option>
                                        <option value="feminin">Féminin</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Responsable <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.responsable_equipe" type="text" required placeholder="Capitaine"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ HACKATHON ════════ -->
                    <div v-if="typeCode === 'HACK'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100">
                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre équipe Hackathon</h2>
                                <p class="text-xs text-slate-500">Informations techniques</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Nom de l'équipe <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.nom_equipe_hack" type="text" required
                                           placeholder="Ex: TeamCode Burkina"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"/>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Membres <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.nb_membres_equipe" type="number" min="1" max="6" required placeholder="4"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"/>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Compétences techniques <span class="text-red-500">*</span>
                                </label>
                                <textarea v-model="form.competences_techniques" rows="2" required
                                          placeholder="Ex: Développement web, IA, mobile..."
                                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Stack technologique <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.stack_technologique" type="text" required
                                       placeholder="Ex: Laravel, Vue.js, MySQL"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"/>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ FORMATION ════════ -->
                    <div v-if="typeCode === 'FORMATION'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre profil et objectifs</h2>
                                <p class="text-xs text-slate-500">Aidez-nous à adapter la formation</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Niveau actuel <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label v-for="n in [
                                        { value: 'debutant', label: 'Débutant', desc: 'Aucune base' },
                                        { value: 'intermediaire', label: 'Intermédiaire', desc: 'Quelques bases' },
                                        { value: 'avance', label: 'Avancé', desc: 'Bonne maîtrise' },
                                    ]" :key="n.value"
                                    :class="['cursor-pointer rounded-xl border-2 p-3 text-center transition',
                                        form.niveau_formation === n.value
                                            ? 'border-emerald-500 bg-emerald-50'
                                            : 'border-slate-200 bg-white hover:border-emerald-300']">
                                        <input type="radio" v-model="form.niveau_formation" :value="n.value" class="sr-only"/>
                                        <p class="font-bold text-slate-900">{{ n.label }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ n.desc }}</p>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Objectifs d'apprentissage <span class="text-red-500">*</span>
                                </label>
                                <textarea v-model="form.objectifs_apprentissage" rows="3" required
                                          placeholder="Que souhaitez-vous apprendre ?"
                                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"/>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SALON ════════ -->
                    <div v-if="typeCode === 'SALON'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre profil professionnel</h2>
                                <p class="text-xs text-slate-500">Pour mieux vous accueillir sur le stand</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Secteur d'activité <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.secteur_activite" type="text" required
                                       placeholder="Ex: Artisanat, Commerce, Agriculture..."
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Type de visite <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <label v-for="t in [
                                        { value: 'visiteur', label: 'Visiteur', desc: 'Découvrir Moov' },
                                        { value: 'partenaire_potentiel', label: 'Partenaire', desc: 'Collaboration B2B' },
                                        { value: 'client_potentiel', label: 'Client', desc: 'Solutions Moov' },
                                    ]" :key="t.value"
                                    :class="['cursor-pointer rounded-xl border-2 p-3 text-center transition',
                                        form.type_visite_salon === t.value
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-slate-200 bg-white hover:border-indigo-300']">
                                        <input type="radio" v-model="form.type_visite_salon" :value="t.value" class="sr-only"/>
                                        <p class="font-bold text-slate-900">{{ t.label }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ t.desc }}</p>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Centres d'intérêt B2B
                                    <span class="font-normal text-slate-400">— optionnel</span>
                                </label>
                                <textarea v-model="form.interets_b2b" rows="2"
                                          placeholder="Ex: Solutions de paiement mobile..."
                                          class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"/>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ CHALLENGE ════════ -->
                    <div v-if="typeCode === 'CHALLENGE'"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100">
                                <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Votre idée innovante</h2>
                                <p class="text-xs text-slate-500">Présentez votre projet</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Titre de votre idée <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.titre_idee" type="text" required
                                       placeholder="Ex: Application de microfinance pour zones rurales"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    Secteur <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.secteur_idee" required
                                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
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

                    <!-- ════════ TARIFICATION ════════ -->
                    <div v-if="evenement.tarifs?.length"
                         class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-moov-blue/10">
                                <svg class="h-5 w-5 text-moov-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-display text-lg font-bold text-slate-900">Tarification</h2>
                                <p class="text-xs text-slate-500">Choisissez votre formule</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label v-for="t in evenement.tarifs" :key="t.id"
                                   :class="['cursor-pointer rounded-xl border-2 p-4 transition',
                                       form.tarif_id === t.id
                                           ? 'border-moov-blue bg-moov-blue/5 shadow-sm'
                                           : 'border-slate-200 bg-white hover:border-moov-blue/40']">
                                <input type="radio" v-model="form.tarif_id" :value="t.id" class="sr-only"/>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ t.libelle }}</p>
                                        <p v-if="t.montant > 0" class="mt-1 font-display text-2xl font-extrabold text-moov-blue">
                                            {{ Number(t.montant).toLocaleString('fr-FR') }}
                                            <span class="text-sm font-bold">{{ t.devise }}</span>
                                        </p>
                                        <p v-else class="mt-1 font-display text-2xl font-extrabold text-emerald-600">
                                            Gratuit
                                        </p>
                                    </div>
                                    <div :class="['flex h-5 w-5 items-center justify-center rounded-full border-2',
                                        form.tarif_id === t.id ? 'border-moov-blue' : 'border-slate-300']">
                                        <div v-if="form.tarif_id === t.id" class="h-2.5 w-2.5 rounded-full bg-moov-blue"/>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- ════════ ACCEPTATION DU RÈGLEMENT ════════ -->
                    <div v-if="evenement.reglement_pdf_url"
                         class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" v-model="form.reglement_accepte" required
                                   class="mt-1 h-5 w-5 rounded border-2 border-slate-300 text-moov-blue focus:ring-2 focus:ring-moov-blue/20"/>
                            <span class="text-sm text-slate-700">
                                <strong class="text-slate-900">J'ai pris connaissance du règlement</strong>
                                et j'accepte les conditions de participation à
                                <strong class="text-slate-900">« {{ evenement.titre }} »</strong>.
                                <a :href="evenement.reglement_pdf_url" target="_blank"
                                   class="font-bold text-moov-orange underline hover:text-moov-orange-dark">
                                    Relire le règlement
                                </a>
                            </span>
                        </label>
                        <p v-if="form.errors.reglement_accepte" class="ml-8 mt-2 text-xs font-bold text-red-600">
                            {{ form.errors.reglement_accepte }}
                        </p>
                    </div>

                   
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p v-if="estPayant" class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Tarif sélectionné
                                </p>
                                <p v-if="estPayant" class="mt-1 font-display text-2xl font-extrabold text-moov-blue">
                                    {{ Number(tarifSelectionne?.montant ?? 0).toLocaleString('fr-FR') }}
                                    <span class="text-sm">{{ tarifSelectionne?.devise }}</span>
                                </p>
                                <p v-else class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Inscription gratuite
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <Link :href="`/evenements/${evenement.id}`"
                                      class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                    Annuler
                                </Link>
                                <button type="submit"
                                        :disabled="!peutSoumettre"
                                        class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:cursor-not-allowed disabled:opacity-40">
                                    <span v-if="form.processing">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                    </span>
                                    {{ form.processing ? 'Envoi en cours...' : 'Soumettre mon dossier' }}
                                    <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </PublicLayout>
</template>