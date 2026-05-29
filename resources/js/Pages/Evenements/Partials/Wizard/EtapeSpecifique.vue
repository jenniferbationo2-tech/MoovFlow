<script setup>
defineProps({
    form: { type: Object, required: true },
    type: { type: Object, required: true },
})

defineEmits(['precedent', 'suivant'])

// Listes pour multi-select
const domainesBaraMousso = [
    'Agriculture', 'Artisanat', 'Commerce', 'Numérique',
    'Mode', 'Restauration', 'Services', 'Industrie',
]

const stadesChallenge = [
    { val: 'Idee', label: 'Idée seulement' },
    { val: 'Prototype', label: 'Prototype en développement' },
    { val: 'Lance', label: 'Déjà lancé sur le marché' },
]

const technologiesHack = [
    'Web', 'Mobile', 'IA / Machine Learning', 'Blockchain',
    'IoT', 'Data Science', 'Cloud', 'Cybersécurité',
]

const toggleDomaine = (form, domaine) => {
    if (!form.domaines_acceptes) form.domaines_acceptes = []
    const idx = form.domaines_acceptes.indexOf(domaine)
    if (idx > -1) form.domaines_acceptes.splice(idx, 1)
    else form.domaines_acceptes.push(domaine)
}

const toggleStade = (form, stade) => {
    if (!form.stades_acceptes) form.stades_acceptes = []
    const idx = form.stades_acceptes.indexOf(stade)
    if (idx > -1) form.stades_acceptes.splice(idx, 1)
    else form.stades_acceptes.push(stade)
}

const toggleTech = (form, tech) => {
    if (!form.technologies_suggerees) form.technologies_suggerees = []
    const idx = form.technologies_suggerees.indexOf(tech)
    if (idx > -1) form.technologies_suggerees.splice(idx, 1)
    else form.technologies_suggerees.push(tech)
}

const onDocumentJointChange = (form, e) => {
    form.document_joint = e.target.files[0]
}

// ═══ GESTION DES CONFÉRENCIERS (type CONF) ═══
const ajouterConferencier = (form) => {
    if (!Array.isArray(form.conferenciers)) {
        form.conferenciers = []
    }
    form.conferenciers.push({
        prenom: '',
        nom: '',
        fonction: '',
        bio: '',
    })
}

const supprimerConferencier = (form, index) => {
    form.conferenciers.splice(index, 1)
}
</script>

<template>
    <div class="rounded-2xl bg-white shadow-card">

        <div class="border-b border-border-soft p-6">
            <h2 class="font-display text-2xl font-extrabold text-text-main">
                Configuration {{ type.nom }}
            </h2>
            <p class="mt-1 text-sm text-text-sub">
                Champs spécifiques à votre type d'événement
            </p>
        </div>

        <div class="p-6">

            <!-- ════════ BARA MOUSSO ════════ -->
            <div v-if="type.code === 'BARA_MOUSSO'" class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Critères de candidature
                    </label>
                    <textarea v-model="form.criteres_candidature" rows="4"
                              placeholder="Ex: Être une femme, avoir entre 18 et 45 ans, présenter un projet entrepreneurial..."
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Domaines acceptés
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="d in domainesBaraMousso" :key="d"
                                @click="toggleDomaine(form, d)" type="button"
                                :class="['rounded-full px-4 py-2 text-xs font-bold transition',
                                    form.domaines_acceptes?.includes(d)
                                        ? 'bg-rose-600 text-white shadow'
                                        : 'border-2 border-border-soft bg-white text-text-sub hover:border-rose-400']">
                            {{ d }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Dotation principale (FCFA)
                        </label>
                        <input v-model="form.dotation_principale" type="text" inputmode="numeric"
                               @input="form.dotation_principale = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 5000000"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Nombre de lauréates
                        </label>
                        <input v-model="form.nombre_laureates" type="text" inputmode="numeric"
                               @input="form.nombre_laureates = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 3"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Âge minimum
                        </label>
                        <input v-model="form.age_min" type="text" inputmode="numeric"
                               @input="form.age_min = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 18"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Âge maximum
                        </label>
                        <input v-model="form.age_max" type="text" inputmode="numeric"
                               @input="form.age_max = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 45"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>
            </div>

            <!-- ════════ CONFÉRENCE ════════ -->
            <div v-else-if="type.code === 'CONF'" class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Thème principal
                    </label>
                    <input v-model="form.theme_principal" type="text"
                           placeholder="Ex: La transformation digitale en Afrique"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Profession cible
                    </label>
                    <select v-model="form.profession_cible"
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                        <option value="">Sélectionner</option>
                        <option value="Tech">Tech / IT</option>
                        <option value="Finance">Finance</option>
                        <option value="RH">Ressources Humaines</option>
                        <option value="Etudiants">Étudiants</option>
                        <option value="Entrepreneurs">Entrepreneurs</option>
                        <option value="Tous">Tous publics</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Programme / Agenda
                    </label>
                    <textarea v-model="form.programme_agenda" rows="6"
                              placeholder="Ex:&#10;09:00 - Accueil&#10;09:30 - Ouverture&#10;10:00 - Conférence 1&#10;11:00 - Pause&#10;..."
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>
                <!-- ═══ CONFÉRENCIERS (dynamique) ═══ -->
                <div class="rounded-xl border-2 border-rose-100 bg-rose-50/30 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <label class="block text-sm font-bold text-text-main">
                                Conférenciers / Intervenants
                            </label>
                            <p class="mt-0.5 text-xs text-text-sub">
                                Liste des intervenants prévus pour cette conférence
                            </p>
                        </div>
                        <button type="button" @click="ajouterConferencier(form)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-moov-blue px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter
                        </button>
                    </div>

                    <!-- Liste des conférenciers -->
                    <div v-if="form.conferenciers && form.conferenciers.length > 0" class="space-y-3">
                        <div v-for="(conf, index) in form.conferenciers" :key="index"
                             class="rounded-lg border border-rose-200 bg-white p-4">

                            <!-- Header : numéro + supprimer -->
                            <div class="mb-3 flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-700">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-rose-100 text-[10px]">{{ index + 1 }}</span>
                                    Conférencier
                                </span>
                                <button type="button" @click="supprimerConferencier(form, index)"
                                    class="rounded p-1 text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                    title="Supprimer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Prénom + Nom -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-text-sub">Prénom *</label>
                                    <input type="text" v-model="conf.prenom" required
                                        placeholder="Jean"
                                        class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-text-sub">Nom *</label>
                                    <input type="text" v-model="conf.nom" required
                                        placeholder="OUEDRAOGO"
                                        class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                                </div>
                            </div>

                            <!-- Fonction -->
                            <div class="mt-3">
                                <label class="mb-1 block text-xs font-bold text-text-sub">Fonction / Titre</label>
                                <input type="text" v-model="conf.fonction"
                                    placeholder="Ex: Directeur Innovation Moov Africa"
                                    class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            </div>

                            <!-- Bio -->
                            <div class="mt-3">
                                <label class="mb-1 block text-xs font-bold text-text-sub">Bio courte</label>
                                <textarea v-model="conf.bio" rows="2"
                                    placeholder="Expert en transformation digitale avec 15 ans d'expérience..."
                                    class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            </div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div v-else
                         class="rounded-lg border-2 border-dashed border-rose-200 bg-white py-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                        <p class="mt-2 text-sm text-text-sub">
                            Aucun conférencier ajouté
                        </p>
                        <p class="mt-1 text-xs text-text-muted">
                            Cliquez sur "Ajouter" pour commencer
                        </p>
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-lg border-2 border-border-soft p-4 cursor-pointer hover:bg-page-bg/50">
                    <input v-model="form.diffusion_en_ligne" type="checkbox"
                           class="mt-0.5 h-5 w-5 rounded border-border-soft text-moov-blue"/>
                    <div>
                        <p class="text-sm font-bold text-text-main">Diffusion en ligne</p>
                        <p class="text-xs text-text-sub">La conférence sera retransmise en direct (Zoom, YouTube...)</p>
                    </div>
                </label>

                <div v-if="form.diffusion_en_ligne">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Lien de diffusion
                    </label>
                    <input v-model="form.lien_zoom" type="url"
                           placeholder="https://zoom.us/j/..."
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Document joint (PDF)
                    </label>
                    <div class="rounded-lg border-2 border-dashed border-border-soft bg-page-bg/30 p-4">
                        <input @change="onDocumentJointChange(form, $event)" type="file" accept="application/pdf"
                               class="block w-full text-sm text-text-sub file:mr-3 file:rounded-lg file:border-0 file:bg-moov-blue file:px-4 file:py-2 file:text-xs file:font-bold file:uppercase file:tracking-wider file:text-white"/>
                    </div>
                </div>
            </div>

            <!-- ════════ TOURNOI SPORTIF ════════ -->
            <div v-else-if="type.code === 'SPORT'" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Discipline
                        </label>
                        <select v-model="form.discipline"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner</option>
                            <option value="Football">Football</option>
                            <option value="Basketball">Basketball</option>
                            <option value="Volleyball">Volleyball</option>
                            <option value="Handball">Handball</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Catégorie d'âge
                        </label>
                        <select v-model="form.categorie_age"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner</option>
                            <option value="U17">U17 (moins de 17 ans)</option>
                            <option value="U20">U20 (moins de 20 ans)</option>
                            <option value="Senior">Senior</option>
                            <option value="Veteran">Vétéran (35+ ans)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Nombre maximum d'équipes
                    </label>
                    <input v-model="form.nombre_max_equipes" type="text" inputmode="numeric"
                           @input="form.nombre_max_equipes = $event.target.value.replace(/\D/g, '')"
                           placeholder="Ex: 16"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Effectif minimum par équipe
                        </label>
                        <input v-model="form.effectif_min" type="text" inputmode="numeric"
                               @input="form.effectif_min = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 11"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Effectif maximum par équipe
                        </label>
                        <input v-model="form.effectif_max" type="text" inputmode="numeric"
                               @input="form.effectif_max = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 15"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Format de la compétition
                    </label>
                    <select v-model="form.format_competition"
                            class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                        <option value="">Sélectionner</option>
                        <option value="Poules">Phase de poules uniquement</option>
                        <option value="Elimination">Élimination directe</option>
                        <option value="Mixte">Mixte (poules + élimination)</option>
                        <option value="Championnat">Championnat</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Trophées et prix
                    </label>
                    <textarea v-model="form.trophees_prix" rows="3"
                              placeholder="Ex:&#10;1er : Trophée + 2 000 000 FCFA&#10;2ème : 1 000 000 FCFA&#10;3ème : 500 000 FCFA"
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>
            </div>

            <!-- ════════ CHALLENGE INNOVATION ════════ -->
            <div v-else-if="type.code === 'CHALLENGE'" class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Thématique du challenge
                    </label>
                    <input v-model="form.thematique_challenge" type="text"
                           placeholder="Ex: Solutions numériques pour l'agriculture"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Critères d'évaluation
                    </label>
                    <textarea v-model="form.criteres_evaluation" rows="4"
                              placeholder="Ex: Innovation (30%), Faisabilité (25%), Impact social (25%), Modèle économique (20%)"
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Stades de maturité acceptés
                    </label>
                    <div class="space-y-2">
                        <label v-for="s in stadesChallenge" :key="s.val"
                               class="flex items-center gap-3 rounded-lg border-2 border-border-soft p-3 cursor-pointer hover:bg-page-bg/50"
                               :class="form.stades_acceptes?.includes(s.val) ? 'border-violet-500 bg-violet-50' : ''">
                            <input type="checkbox"
                                   :checked="form.stades_acceptes?.includes(s.val)"
                                   @change="toggleStade(form, s.val)"
                                   class="h-5 w-5 rounded border-border-soft text-violet-600"/>
                            <span class="text-sm font-bold text-text-main">{{ s.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Dotation totale (FCFA)
                        </label>
                        <input v-model="form.dotation_totale" type="text" inputmode="numeric"
                               @input="form.dotation_totale = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 10000000"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Date de clôture des dossiers
                        </label>
                        <input v-model="form.date_cloture_dossiers" type="date"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>
            </div>

            <!-- ════════ FORMATION ════════ -->
            <div v-else-if="type.code === 'FORMATION'" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Domaine
                        </label>
                        <select v-model="form.domaine_formation"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner</option>
                            <option value="IA_Data">IA & Data Science</option>
                            <option value="Web">Développement Web</option>
                            <option value="Mobile">Développement Mobile</option>
                            <option value="Marketing">Marketing Digital</option>
                            <option value="Cyber">Cybersécurité</option>
                            <option value="Cloud">Cloud Computing</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Niveau requis
                        </label>
                        <select v-model="form.niveau_requis"
                                class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue">
                            <option value="">Sélectionner</option>
                            <option value="Debutant">Débutant complet</option>
                            <option value="Intermediaire">Intermédiaire</option>
                            <option value="Avance">Avancé / Expert</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Durée (en heures)
                    </label>
                    <input v-model="form.duree_heures" type="text" inputmode="numeric"
                           @input="form.duree_heures = $event.target.value.replace(/\D/g, '')"
                           placeholder="Ex: 40"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <label class="flex items-start gap-3 rounded-lg border-2 border-border-soft p-4 cursor-pointer hover:bg-page-bg/50">
                    <input v-model="form.certification" type="checkbox"
                           class="mt-0.5 h-5 w-5 rounded border-border-soft text-moov-blue"/>
                    <div>
                        <p class="text-sm font-bold text-text-main">Certification délivrée</p>
                        <p class="text-xs text-text-sub">Les participants reçoivent un certificat à l'issue de la formation</p>
                    </div>
                </label>

                <div v-if="form.certification">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Nom de la certification
                    </label>
                    <input v-model="form.nom_certification" type="text"
                           placeholder="Ex: Certification Moov Africa - Développeur Web"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Programme détaillé
                    </label>
                    <textarea v-model="form.programme_detaille" rows="5"
                              placeholder="Ex:&#10;Module 1 - HTML/CSS (8h)&#10;Module 2 - JavaScript (12h)&#10;Module 3 - Vue.js (20h)..."
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Matériel requis
                    </label>
                    <textarea v-model="form.materiel_requis" rows="2"
                              placeholder="Ex: Ordinateur portable avec 8 Go de RAM minimum"
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>
            </div>

            <!-- ════════ HACKATHON ════════ -->
            <div v-else-if="type.code === 'HACK'" class="space-y-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Thème du hackathon
                    </label>
                    <input v-model="form.theme_hackathon" type="text"
                           placeholder="Ex: Fintech pour l'inclusion financière"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Durée
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label v-for="d in [24, 48, 72]" :key="d"
                               class="cursor-pointer rounded-lg border-2 p-3 text-center text-sm font-bold transition"
                               :class="form.duree_heures_hack === d
                                   ? 'border-moov-orange bg-orange-50 text-orange-700'
                                   : 'border-border-soft text-text-sub hover:border-orange-300'">
                            <input type="radio" v-model="form.duree_heures_hack" :value="d" class="sr-only"/>
                            {{ d }}h
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Taille équipe minimum
                        </label>
                        <input v-model="form.equipe_min" type="text" inputmode="numeric"
                               @input="form.equipe_min = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 2"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Taille équipe maximum
                        </label>
                        <input v-model="form.equipe_max" type="text" inputmode="numeric"
                               @input="form.equipe_max = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 5"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Technologies suggérées
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="t in technologiesHack" :key="t"
                                @click="toggleTech(form, t)" type="button"
                                :class="['rounded-full px-4 py-2 text-xs font-bold transition',
                                    form.technologies_suggerees?.includes(t)
                                        ? 'bg-moov-orange text-white shadow'
                                        : 'border-2 border-border-soft bg-white text-text-sub hover:border-orange-400']">
                            {{ t }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Critères d'évaluation
                    </label>
                    <textarea v-model="form.criteres_evaluation_hack" rows="4"
                              placeholder="Ex: Innovation, Qualité technique, Impact, Présentation"
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>
            </div>

            <!-- ════════ SALON ════════ -->
            <div v-else-if="type.code === 'SALON'" class="space-y-5">
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-sm font-bold text-indigo-900">
                        À propos du Salon
                    </p>
                    <p class="mt-1 text-xs text-indigo-800">
                        Cet événement est organisé par un tiers. Moov y tient un stand. Aucune inscription en ligne nécessaire.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Nom du salon hôte
                        </label>
                        <input v-model="form.nom_salon_hote" type="text"
                               placeholder="Ex: SIAO 2026"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Organisateur externe
                        </label>
                        <input v-model="form.organisateur_externe" type="text"
                               placeholder="Ex: Office National du Tourisme"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Emplacement du stand
                        </label>
                        <input v-model="form.lieu_stand" type="text"
                               placeholder="Ex: Hall A, Allée 3, Stand 12"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Superficie (m²)
                        </label>
                        <input v-model="form.superficie_stand" type="text" inputmode="numeric"
                               @input="form.superficie_stand = $event.target.value.replace(/\D/g, '')"
                               placeholder="Ex: 18"
                               class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Objectifs du stand
                    </label>
                    <textarea v-model="form.objectifs_stand" rows="3"
                              placeholder="Ex: Promouvoir les nouvelles offres Moov Money, collecter des prospects B2B..."
                              class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Objectif prospects à collecter
                    </label>
                    <input v-model="form.objectif_prospects" type="text" inputmode="numeric"
                           @input="form.objectif_prospects = $event.target.value.replace(/\D/g, '')"
                           placeholder="Ex: 500"
                           class="w-full rounded-lg border-2 border-border-soft bg-white px-4 py-3 text-sm outline-none focus:border-moov-blue"/>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-border-soft bg-page-bg/30 p-6">
            <button @click="$emit('precedent')"
                    class="flex items-center gap-2 rounded-lg border-2 border-border-soft bg-white px-5 py-2.5 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </button>
            <button @click="$emit('suivant')"
                    class="flex items-center gap-2 rounded-lg bg-moov-blue px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-blue-dark">
                Suivant : Récapitulatif
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</template>