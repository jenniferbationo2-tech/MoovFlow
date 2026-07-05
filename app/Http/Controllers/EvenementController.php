<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Lieu;
use App\Models\TypeEvenement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EvenementController extends Controller
{

    public function index(Request $request): Response
    {
        $now = now();
        $user = Auth::user();
        $estStaff = $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);

        $query = Evenement::query()
            ->with(['typeEvenement', 'lieu', 'tarifs'])
            ->withCount('inscriptions');


        if ($request->filled('type')) {
            $query->whereHas('typeEvenement', fn($q) => $q->where('code', $request->type));
        }

        // Si non-staff : uniquement les publiés / en_cours / terminé
        if (!$estStaff) {
            $query->whereIn('statut', ['publie', 'en_cours', 'termine']);
        }

        $evenements = $query->latest('date_debut')->paginate(12);

        // CALCUL DES KPIs (basé sur tous les events visibles) 
        $statutsAffichables = ['publie', 'en_cours', 'termine'];
        $baseQueryKpi = Evenement::query()
            ->whereIn('statut', $statutsAffichables);

        $totalEvenements = (clone $baseQueryKpi)
            ->whereIn('statut', ['publie', 'en_cours'])  // À venir ou en cours seulement
            ->count();

        $enCoursCount = (clone $baseQueryKpi)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->count();

        $typologiesCount = TypeEvenement::count();

        $types = TypeEvenement::orderBy('nom')->get();

        return Inertia::render('Evenements/Index', [
            'evenements' => $evenements,
            'types'      => $types,
            'filters'    => $request->only(['type']),
            'kpis'       => [
                'total'      => $totalEvenements,
                'en_cours'   => $enCoursCount,
                'typologies' => $typologiesCount,
            ],
            'now'        => $now->toIso8601String(),  // Date courante pour statuts dynamiques côté Vue
        ]);
    }


    public function show(Evenement $evenement): Response
    {
        $evenement->load([
            'typeEvenement',
            'lieu',
            'tarifs',
        ]);

        try {
            $evenement->load('objectifsRse');
        } catch (\Exception $e) {
            
        }


        $postesBenevoles = $evenement->postesBenevoles()
            ->where('statut', 'ouvert')
            ->withCount(['candidatures as places_acceptees' => function ($q) {
                $q->where('statut', 'accepte');
            }])
            ->get()
            ->map(function ($poste) {
                $poste->places_restantes = max(0, $poste->places_max - $poste->places_acceptees);
                return $poste;
            })
            ->filter(fn($p) => $p->places_restantes > 0)
            ->values();

        return Inertia::render('Evenements/Show', [
            'evenement'       => $evenement,
            'postesBenevoles' => $postesBenevoles,  
        ]);
    }

/*formulaire de création d'événement*/
    public function create(Request $request): Response
    {
        $user = Auth::user();
        $this->authorizeCreation();

        $types = TypeEvenement::orderBy('nom')->get();
        $lieux = Lieu::orderBy('nom')->get();

        // Si un type est pré-sélectionné via query string (?type=CONF)
        $typePreselectionne = null;
        if ($request->filled('type')) {
            $typePreselectionne = TypeEvenement::where('code', $request->type)->first();
        }

        return Inertia::render('Evenements/Create', [
            'types'              => $types,
            'lieux'              => $lieux,
            'typePreselectionne' => $typePreselectionne,
            'userRole'           => [
                'estResponsable' => $user->hasAnyRole(['admin', 'responsable_dcirp']),
                'estOrganisateur' => $user->hasRole('organisateur'),
            ]
        ]);
    }

    /**
     * Création d'un événement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreation();

        // Récupérer le type pour validation conditionnelle
        $type = TypeEvenement::findOrFail($request->type_evenement_id);
        $typeCode = $type->code;

        //  VALIDATION COMMUNE 
        $rules = [
            'titre'                => ['required', 'string', 'max:300'],
            'description'          => ['nullable', 'string'],
            'type_evenement_id'    => ['required', 'exists:types_evenement,id'],
            'lieu_id'              => ['required', 'exists:lieux,id'],
            'date_debut'           => ['required', 'date'],
            'date_fin'             => ['required', 'date', 'after_or_equal:date_debut'],
            'capacite_max'         => ['nullable', 'integer', 'min:0'],
            'budget_previsionnel'  => ['nullable', 'numeric', 'min:0'],
            'public_cible'         => ['nullable', 'string', 'max:200'],
            'cible_beneficiaires'  => ['nullable', 'integer', 'min:0'],
            'objectifs_principaux' => ['nullable', 'string', 'max:2000'],
            'visuel'               => ['nullable', 'image', 'max:5120'],
            'reglement_pdf'        => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
            'tarifs'               => ['nullable', 'array'],
            'tarifs.*.nom'         => ['required_with:tarifs', 'string', 'max:200'],
            'tarifs.*.montant'     => ['required_with:tarifs', 'numeric', 'min:0'],
        ];


        $rules = array_merge($rules, $this->getReglesValidationParType($typeCode));

        $validated = $request->validate($rules);

        $data = collect($validated)
            ->except(['visuel', 'reglement_pdf', 'tarifs'])
            ->merge([
                'statut'        => Auth::user()->hasRole('responsable_dcirp') ? 'publie' : 'brouillon',
                'created_by'    => Auth::id(),
                'validated_by'  => Auth::user()->hasRole('responsable_dcirp') ? Auth::id() : null,
                'date_publication' => Auth::user()->hasRole('responsable_dcirp') ? now() : null,
            ])
            ->toArray();

        // ── UPLOAD FICHIERS ──
        if ($request->hasFile('visuel')) {
            $data['visuel'] = $request->file('visuel')->store('evenements/visuels', 'public');
        }
        if ($request->hasFile('reglement_pdf')) {
            $data['reglement_pdf'] = $request->file('reglement_pdf')->store('evenements/reglements', 'public');
        }
        if ($request->hasFile('document_joint')) {
            $data['document_joint'] = $request->file('document_joint')->store('evenements/documents', 'public');
        }

        // ── CRÉATION ──
        $evenement = Evenement::create($data);

        // ── TARIFS ──
        if (!empty($validated['tarifs'])) {
            foreach ($validated['tarifs'] as $tarif) {
                $evenement->tarifs()->create([
                    'nom'     => $tarif['nom'],
                    'montant' => $tarif['montant'],
                ]);
            }
        }

        $estResponsable = Auth::user()->hasRole('responsable_dcirp');
        $messageSucces = $estResponsable
            ? 'Événement créé et publié avec succès !'
            : 'Événement créé en statut Brouillon. Vous pouvez maintenant demander la validation au responsable dCIRP.';

        return redirect()->route('evenements.show', $evenement->id)
            ->with('success', $messageSucces);
    }


    public function edit(Evenement $evenement): Response
    {
        $this->authorizeEdition($evenement);

        $evenement->load(['typeEvenement', 'lieu', 'tarifs']);

        $types = TypeEvenement::orderBy('nom')->get();
        $lieux = Lieu::orderBy('nom')->get();

        return Inertia::render('Evenements/Edit', [
            'evenement' => $evenement,
            'types'     => $types,
            'lieux'     => $lieux,
        ]);
    }

    /**
     * Mise à jour d'un événement.
     */
    public function update(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeEdition($evenement);

        $type = TypeEvenement::findOrFail($request->type_evenement_id ?? $evenement->type_evenement_id);
        $typeCode = $type->code;

        $rules = [
            'titre'                => ['required', 'string', 'max:300'],
            'description'          => ['nullable', 'string'],
            'type_evenement_id'    => ['required', 'exists:types_evenement,id'],
            'lieu_id'              => ['required', 'exists:lieux,id'],
            'date_debut'           => ['required', 'date'],
            'date_fin'             => ['required', 'date', 'after_or_equal:date_debut'],
            'capacite_max'         => ['nullable', 'integer', 'min:0'],
            'budget_previsionnel'  => ['nullable', 'numeric', 'min:0'],
            'public_cible'         => ['nullable', 'string', 'max:200'],
            'cible_beneficiaires'  => ['nullable', 'integer', 'min:0'],
            'objectifs_principaux' => ['nullable', 'string', 'max:2000'],
            'visuel'               => ['nullable', 'image', 'max:5120'],
            'reglement_pdf'        => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ];

        $rules = array_merge($rules, $this->getReglesValidationParType($typeCode));

        $validated = $request->validate($rules);

        $data = collect($validated)
            ->except(['visuel', 'reglement_pdf'])
            ->toArray();

        if ($request->hasFile('visuel')) {
            // Supprimer l'ancien visuel
            if ($evenement->visuel) {
                Storage::disk('public')->delete($evenement->visuel);
            }
            $data['visuel'] = $request->file('visuel')->store('evenements/visuels', 'public');
        }

        if ($request->hasFile('reglement_pdf')) {
            if ($evenement->reglement_pdf) {
                Storage::disk('public')->delete($evenement->reglement_pdf);
            }
            $data['reglement_pdf'] = $request->file('reglement_pdf')->store('evenements/reglements', 'public');
        }

        $evenement->update($data);

        return redirect()->route('evenements.show', $evenement->id)
            ->with('success', 'Événement mis à jour avec succès !');
    }

    /**
     * Suppression (archivage) d'un événement.
     */
    public function destroy(Evenement $evenement): RedirectResponse
    {
        $this->authorizeEdition($evenement);

        $evenement->update(['statut' => 'annule']);

        return redirect()->route('evenements.index')
            ->with('success', 'Événement archivé avec succès.');
    }

    public function demanderValidation(Evenement $evenement): RedirectResponse
    {
        $user = Auth::user();

        abort_unless(
            $evenement->created_by === $user->id || $user->hasRole('admin'),
            403,
            'Vous n\'êtes pas autorisé à demander la validation.'
        );

        abort_unless(
            $evenement->statut === 'brouillon',
            422,
            'Cet événement n\'est pas en brouillon.'
        );

        $evenement->update([
            'statut'                  => 'en_validation',
            'date_demande_validation' => now(),
        ]);

        return back()->with('success', 'Demande de validation envoyée au responsable dCIRP.');
    }

    public function valider(Evenement $evenement): RedirectResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['responsable_dcirp', 'admin']),
            403,
            'Seul le responsable dCIRP peut valider les événements.'
        );

        abort_unless(
            in_array($evenement->statut, ['brouillon', 'en_validation']),
            422,
            'Cet événement ne peut pas être validé dans son statut actuel.'
        );

        $evenement->update([
            'statut'           => 'publie',
            'date_publication' => now(),
            'validated_by'     => $user->id,
            'motif_rejet'      => null,
        ]);

        return back()->with('success', 'Événement validé et publié avec succès !');
    }

    public function rejeter(Evenement $evenement, Request $request): RedirectResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['responsable_dcirp', 'admin']),
            403,
            'Action non autorisée.'
        );

        $request->validate([
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $evenement->update([
            'statut'      => 'brouillon',
            'motif_rejet' => $request->motif_rejet,
        ]);

        return back()->with('success', 'Événement renvoyé en brouillon avec motif.');
    }

    /**
     * Demander des modifications à l'organisateur avant validation.
     * L'événement repasse en "brouillon" et l'organisateur est notifié.
     */
    public function demanderModifications(Request $request, Evenement $evenement): RedirectResponse
    {
        $user = Auth::user();

        // Seul un responsable peut demander des modifications
        abort_unless(
            $user && $user->hasRole('responsable_dcirp'),
            403,
            'Seul le responsable dCIRP peut demander des modifications.'
        );

        // L'événement doit être en validation
        abort_unless(
            in_array($evenement->statut, ['en_validation', 'brouillon']),
            422,
            'Cet événement ne peut pas faire l\'objet d\'une demande de modifications.'
        );

        // Validation du message
        $validated = $request->validate([
            'modifications_demandees' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'modifications_demandees.required' => 'Veuillez préciser les modifications à apporter.',
            'modifications_demandees.min' => 'Le message doit contenir au moins 10 caractères.',
        ]);

        // Mise à jour de l'événement
        $evenement->update([
            'statut'                       => 'brouillon',
            'modifications_demandees'      => $validated['modifications_demandees'],
            'modifications_demandees_le'   => now(),
            'modifications_demandees_par'  => $user->id,
        ]);


        return back()->with(
            'success',
            'Demande de modifications envoyée à l\'organisateur. L\'événement repasse en brouillon.'
        );
    }

    public function updateStatut(Request $request, Evenement $evenement): RedirectResponse
    {
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403,
            'Action non autorisée.'
        );

        $request->validate([
            'statut' => ['required', 'in:brouillon,en_validation,publie,en_cours,termine,annule'],
        ]);

        $evenement->update(['statut' => $request->statut]);

        return back()->with('success', 'Statut mis à jour.');
    }

    private function authorizeCreation(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['responsable_dcirp', 'organisateur']),
            403,
            'Les administrateurs ne peuvent pas créer d\'événements. Seuls le responsable dCIRP et les organisateurs en ont le droit.'
        );
    }

    private function authorizeEdition(Evenement $evenement): void
    {
        $user = Auth::user();
        $canEdit = $user && (
            $user->hasRole('admin') ||
            ($user->hasRole('responsable_dcirp')) ||
            ($evenement->created_by === $user->id)
        );

        abort_unless($canEdit, 403, 'Vous n\'êtes pas autorisé à modifier cet événement.');
    }


    private function getReglesValidationParType(string $typeCode): array
    {
        return match ($typeCode) {
            'BARA_MOUSSO' => [
                'criteres_candidature' => ['nullable', 'string', 'max:2000'],
                'domaines_acceptes'    => ['nullable', 'array'],
                'domaines_acceptes.*'  => ['string'],
                'dotation_principale'  => ['nullable', 'numeric', 'min:0'],
                'nombre_laureates'     => ['nullable', 'integer', 'min:1'],
                'age_min'              => ['nullable', 'integer', 'min:18', 'max:99'],
                'age_max'              => ['nullable', 'integer', 'min:18', 'max:99', 'gte:age_min'],
            ],
            'CONF' => [
                'theme_principal'    => ['nullable', 'string', 'max:300'],
                'profession_cible'   => ['nullable', 'string', 'max:100'],
                'programme_agenda'   => ['nullable', 'string', 'max:5000'],
                'diffusion_en_ligne' => ['boolean'],
                'lien_zoom'          => ['nullable', 'url', 'max:500'],
                'document_joint'     => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
                'conferenciers'             => ['nullable', 'array'],
                'conferenciers.*.prenom'    => ['required_with:conferenciers', 'string', 'max:100'],
                'conferenciers.*.nom'       => ['required_with:conferenciers', 'string', 'max:100'],
                'conferenciers.*.fonction'  => ['nullable', 'string', 'max:200'],
                'conferenciers.*.bio'       => ['nullable', 'string', 'max:1000'],
            ],
            'SPORT' => [
                'discipline'         => ['nullable', 'string', 'max:100'],
                'categorie_age'      => ['nullable', 'string', 'max:50'],
                'nombre_max_equipes' => ['nullable', 'integer', 'min:2'],
                'effectif_min'       => ['nullable', 'integer', 'min:1'],
                'effectif_max'       => ['nullable', 'integer', 'min:1', 'gte:effectif_min'],
                'format_competition' => ['nullable', 'string', 'max:100'],
                'trophees_prix'      => ['nullable', 'string', 'max:2000'],
            ],
            'CHALLENGE' => [
                'thematique_challenge'  => ['nullable', 'string', 'max:300'],
                'criteres_evaluation'   => ['nullable', 'string', 'max:2000'],
                'stades_acceptes'       => ['nullable', 'array'],
                'stades_acceptes.*'     => ['string'],
                'dotation_totale'       => ['nullable', 'numeric', 'min:0'],
                'date_cloture_dossiers' => ['nullable', 'date'],
            ],
            'FORMATION' => [
                'domaine_formation'  => ['nullable', 'string', 'max:100'],
                'niveau_requis'      => ['nullable', 'string', 'max:50'],
                'duree_heures'       => ['nullable', 'integer', 'min:1'],
                'certification'      => ['boolean'],
                'nom_certification'  => ['nullable', 'string', 'max:200'],
                'programme_detaille' => ['nullable', 'string', 'max:5000'],
                'materiel_requis'    => ['nullable', 'string', 'max:1000'],
            ],
            'HACK' => [
                'theme_hackathon'         => ['nullable', 'string', 'max:300'],
                'duree_heures_hack'       => ['nullable', 'integer', 'min:1'],
                'equipe_min'              => ['nullable', 'integer', 'min:1'],
                'equipe_max'              => ['nullable', 'integer', 'min:1', 'gte:equipe_min'],
                'technologies_suggerees'  => ['nullable', 'array'],
                'technologies_suggerees.*' => ['string'],
                'criteres_evaluation_hack' => ['nullable', 'string', 'max:2000'],
            ],
            'SALON' => [
                'nom_salon_hote'      => ['nullable', 'string', 'max:300'],
                'organisateur_externe' => ['nullable', 'string', 'max:300'],
                'lieu_stand'          => ['nullable', 'string', 'max:200'],
                'superficie_stand'    => ['nullable', 'integer', 'min:1'],
                'objectifs_stand'     => ['nullable', 'string', 'max:2000'],
                'objectif_prospects'  => ['nullable', 'integer', 'min:0'],
            ],
            default => [],
        };
    }
}
