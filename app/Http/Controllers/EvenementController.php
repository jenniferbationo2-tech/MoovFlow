<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvenementRequest;
use App\Http\Requests\UpdateEvenementRequest;
use App\Models\Budget;
use App\Models\Evenement;
use App\Models\Lieu;
use App\Models\ObjectifRse;
use App\Models\TypeEvenement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EvenementController extends Controller
{
    /**
     * Affiche la liste paginée des événements avec filtres.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Evenement::class);

        $filters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'statut' => $request->string('statut')->toString(),
            'date' => $request->string('date')->toString(),
        ];

        $query = Evenement::query()
            ->with(['typeEvenement', 'lieu.salles'])
            ->withCount('inscriptions');

        if ($filters['search'] !== '') {
            $query->where(function ($builder) use ($filters): void {
                $builder
                    ->where('titre', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($filters['type'] !== '') {
            $query->where('type_evenement_id', $filters['type']);
        }

        if ($filters['statut'] !== '') {
            $query->where('statut', $filters['statut']);
        }

        if ($filters['date'] !== '') {
            $filterDate = Carbon::parse($filters['date']);

            $query
                ->where('date_debut', '<=', $filterDate->copy()->endOfDay())
                ->where('date_fin', '>=', $filterDate->copy()->startOfDay());
        }

        $evenements = $query
            ->orderBy('date_debut')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Evenement $evenement): array => $this->mapEvenementListItem($evenement));

        return Inertia::render('Evenements/Index', [
            'evenements' => $evenements,
            'filters' => $filters,
            'typesEvenement' => $this->typesPayload(),
            'statuts' => self::eventStatuses(),
            'stats' => [
                'total' => Evenement::count(),
                'en_cours' => Evenement::where('statut', 'en_cours')->count(),
                'termines' => Evenement::where('statut', 'termine')->count(),
                'taux_remplissage_moyen' => $this->averageFillRate(),
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create(): Response
    {
        $this->authorize('create', Evenement::class);

        return Inertia::render('Evenements/Create', [
            'typesEvenement' => $this->typesPayload(),
            'lieux' => $this->lieuxPayload(),
            'statuts' => self::eventStatuses(),
        ]);
    }

    /**
     * Enregistre un nouvel événement avec son budget et son QR code.
     */
    public function store(StoreEvenementRequest $request): RedirectResponse
    {
        $this->authorize('create', Evenement::class);

        $validated = $request->validated();
        $evenement = null;

        DB::transaction(function () use ($request, $validated, &$evenement): void {
            $visuelPath = $request->hasFile('visuel')
                ? $request->file('visuel')->store('evenements/visuels', 'public')
                : null;

            $montantPrevisionnel = (float) ($validated['montant_previsionnel'] ?? 0);

            $evenement = Evenement::create([
                'titre' => $validated['titre'],
                'description' => $validated['description'] ?? null,
                'visuel' => $visuelPath,
                'type_evenement_id' => $validated['type_evenement_id'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'lieu_id' => $validated['lieu_id'] ?? null,
                'statut' => $validated['statut'] ?? 'brouillon',
                'budget_prev' => $montantPrevisionnel,
                'created_by' => $request->user()?->id,
            ]);

            $evenement->budgets()->create([
                'montant_previsionnel' => $montantPrevisionnel,
                'devise' => $validated['devise'] ?? 'XOF',
            ]);

            $this->upsertObjectifRse($evenement, $validated);
            $this->generateQrCode($evenement);
        });

        return redirect()
            ->route('evenements.show', $evenement)
            ->with('success', 'Événement créé avec succès.');
    }

    /**
     * Affiche la fiche détaillée d'un événement.
     */
    public function show(Request $request, Evenement $evenement): Response
    {
        $this->authorize('view', $evenement);

        $evenement->load([
            'typeEvenement',
            'lieu',
            'createur',
            'sessions.salle',
            'taches.responsable',
            'budgets.lignesBudget',
            'objectifsRse',
        ])->loadCount('inscriptions');

        return Inertia::render('Evenements/Show', [
            'evenement' => $this->mapEvenementShow($evenement),
            'budget' => $this->mapBudget($evenement->budgets->first()),
            'typesEvenement' => $this->typesPayload(),
            'lieux' => $this->lieuxPayload(),
            'statuts' => self::eventStatuses(),
            'tacheStatuts' => self::taskStatuses(),
            'utilisateurs' => $this->usersPayload(),
            'activeTab' => $request->string('tab')->toString() !== '' ? $request->string('tab')->toString() : 'resume',
        ]);
    }

    /**
     * Affiche le formulaire d'édition.
     */
    public function edit(Evenement $evenement): Response
    {
        $this->authorize('update', $evenement);

        $evenement->load(['budgets', 'objectifsRse']);

        return Inertia::render('Evenements/Edit', [
            'evenement' => $this->mapEvenementForm($evenement),
            'typesEvenement' => $this->typesPayload(),
            'lieux' => $this->lieuxPayload(),
            'statuts' => self::eventStatuses(),
        ]);
    }

    /**
     * Met à jour un événement existant.
     */
    public function update(UpdateEvenementRequest $request, Evenement $evenement): RedirectResponse
    {
        $this->authorize('update', $evenement);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $evenement): void {
            $data = [
                'titre' => $validated['titre'],
                'description' => $validated['description'] ?? null,
                'type_evenement_id' => $validated['type_evenement_id'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'lieu_id' => $validated['lieu_id'] ?? null,
                'statut' => $validated['statut'] ?? $evenement->statut,
                'budget_prev' => (float) ($validated['montant_previsionnel'] ?? $evenement->budget_prev ?? 0),
            ];

            if ($request->hasFile('visuel')) {
                if ($evenement->visuel) {
                    Storage::disk('public')->delete($evenement->visuel);
                }

                $data['visuel'] = $request->file('visuel')->store('evenements/visuels', 'public');
            }

            $evenement->update($data);

            $budget = $evenement->budgets()->firstOrCreate([], [
                'montant_previsionnel' => 0,
                'devise' => 'XOF',
            ]);

            $budget->update([
                'montant_previsionnel' => (float) ($validated['montant_previsionnel'] ?? $budget->montant_previsionnel),
                'devise' => $validated['devise'] ?? $budget->devise,
            ]);

            $this->upsertObjectifRse($evenement, $validated);
            $this->generateQrCode($evenement);
        });

        return redirect()
            ->route('evenements.show', $evenement)
            ->with('success', 'Événement mis à jour avec succès.');
    }

    /**
     * Supprime logiquement un événement.
     */
    public function destroy(Evenement $evenement): RedirectResponse
    {
        $this->authorize('delete', $evenement);

        $evenement->delete();

        return redirect()
            ->route('evenements.index')
            ->with('success', 'Événement supprimé avec succès.');
    }

    /**
     * Met à jour le statut selon le workflow autorisé.
     */
    public function updateStatut(Request $request, Evenement $evenement): RedirectResponse
    {
        $validated = $request->validate([
            'statut' => ['required', 'string'],
        ]);

        if ($validated['statut'] === 'publie') {
            $this->authorize('publish', $evenement);
        } else {
            $this->authorize('update', $evenement);
        }

        if (! $this->canTransitionEventStatus($evenement->statut, $validated['statut'])) {
            return back()->withErrors([
                'statut' => 'Transition de statut non autorisée pour cet événement.',
            ]);
        }

        $evenement->update([
            'statut' => $validated['statut'],
        ]);

        return back()->with('success', 'Statut de l’événement mis à jour.');
    }

    /**
     * Retourne la liste normalisée des statuts d'événement.
     *
     * @return array<int, array<string, string>>
     */
    public static function eventStatuses(): array
    {
        return [
            ['value' => 'brouillon', 'label' => 'Brouillon'],
            ['value' => 'publie', 'label' => 'Publié'],
            ['value' => 'en_cours', 'label' => 'En cours'],
            ['value' => 'termine', 'label' => 'Terminé'],
            ['value' => 'annule', 'label' => 'Annulé'],
        ];
    }

    /**
     * Retourne la liste normalisée des statuts de tâche.
     *
     * @return array<int, array<string, string>>
     */
    public static function taskStatuses(): array
    {
        return [
            ['value' => 'a_faire', 'label' => 'À faire'],
            ['value' => 'en_cours', 'label' => 'En cours'],
            ['value' => 'termine', 'label' => 'Terminée'],
        ];
    }

    /**
     * Prépare les options de types d'événements.
     *
     * @return array<int, array<string, mixed>>
     */
    private function typesPayload(): array
    {
        return TypeEvenement::query()
            ->orderBy('nom')
            ->get()
            ->map(fn (TypeEvenement $type): array => [
                'id' => $type->id,
                'nom' => $type->nom,
                'code' => $type->code,
            ])
            ->all();
    }

    /**
     * Prépare les options de lieux.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lieuxPayload(): array
    {
        return Lieu::query()
            ->orderBy('nom')
            ->get()
            ->map(fn (Lieu $lieu): array => [
                'id' => $lieu->id,
                'nom' => $lieu->nom,
                'adresse' => $lieu->adresse,
            ])
            ->all();
    }

    /**
     * Prépare la liste des utilisateurs pour l'affectation des tâches.
     *
     * @return array<int, array<string, mixed>>
     */
    private function usersPayload(): array
    {
        return User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * Formate un événement pour le tableau d'index.
     *
     * @return array<string, mixed>
     */
    private function mapEvenementListItem(Evenement $evenement): array
    {
        $capacite = $evenement->lieu?->salles?->sum('capacite');
        $inscrits = (int) ($evenement->inscriptions_count ?? 0);
        $remplissage = $capacite && $capacite > 0
            ? min((int) round(($inscrits / $capacite) * 100), 100)
            : null;

        return [
            'id' => $evenement->id,
            'titre' => $evenement->titre,
            'description' => $evenement->description,
            'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
            'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
            'statut' => $evenement->statut,
            'visuel_url' => $this->storageUrl($evenement->visuel),
            'inscriptions_count' => $inscrits,
            'capacite' => $capacite > 0 ? $capacite : null,
            'taux_remplissage' => $remplissage,
            'type_evenement' => $evenement->typeEvenement ? [
                'id' => $evenement->typeEvenement->id,
                'nom' => $evenement->typeEvenement->nom,
                'code' => $evenement->typeEvenement->code,
            ] : null,
            'lieu' => $evenement->lieu ? [
                'id' => $evenement->lieu->id,
                'nom' => $evenement->lieu->nom,
                'adresse' => $evenement->lieu->adresse,
            ] : null,
        ];
    }

    /**
     * Formate un événement pour la fiche détaillée.
     *
     * @return array<string, mixed>
     */
    private function mapEvenementShow(Evenement $evenement): array
    {
        $budget = $evenement->budgets->first();

        return [
            'id' => $evenement->id,
            'titre' => $evenement->titre,
            'description' => $evenement->description,
            'visuel_url' => $this->storageUrl($evenement->visuel),
            'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
            'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
            'statut' => $evenement->statut,
            'budget_prev' => $evenement->budget_prev,
            'inscriptions_count' => $evenement->inscriptions_count,
            'qr_code_url' => $this->storageUrl('qrcodes/evenement-'.$evenement->id.'.svg'),
            'type_evenement' => $evenement->typeEvenement ? [
                'id' => $evenement->typeEvenement->id,
                'nom' => $evenement->typeEvenement->nom,
                'code' => $evenement->typeEvenement->code,
            ] : null,
            'lieu' => $evenement->lieu ? [
                'id' => $evenement->lieu->id,
                'nom' => $evenement->lieu->nom,
                'adresse' => $evenement->lieu->adresse,
            ] : null,
            'organisateur' => $evenement->createur ? [
                'id' => $evenement->createur->id,
                'name' => $evenement->createur->name,
            ] : null,
            'sessions' => $evenement->sessions->map(fn ($session): array => [
                'id' => $session->id,
                'titre' => $session->titre,
                'description' => $session->description,
                'heure_debut' => optional($session->heure_debut)?->toIso8601String(),
                'heure_fin' => optional($session->heure_fin)?->toIso8601String(),
                'salle' => $session->salle ? [
                    'id' => $session->salle->id,
                    'nom' => $session->salle->nom,
                ] : null,
            ])->all(),
            'taches' => $evenement->taches->map(fn ($tache): array => [
                'id' => $tache->id,
                'titre' => $tache->titre,
                'description' => $tache->description,
                'echeance' => optional($tache->echeance)?->toDateString(),
                'statut' => $tache->statut,
                'responsable' => $tache->responsable ? [
                    'id' => $tache->responsable->id,
                    'name' => $tache->responsable->name,
                ] : null,
            ])->values()->all(),
            'budget' => $this->mapBudget($budget),
            'objectifs_rse' => $evenement->objectifsRse->map(fn (ObjectifRse $objectif): array => [
                'id' => $objectif->id,
                'type_impact' => $objectif->type_impact,
                'nb_beneficiaires_directs' => $objectif->nb_beneficiaires_directs,
                'nb_beneficiaires_indirects' => $objectif->nb_beneficiaires_indirects,
                'nb_associations_soutenues' => $objectif->nb_associations_soutenues,
                'nb_projets_accompagnes' => $objectif->nb_projets_accompagnes,
                'nb_femmes_beneficiaires' => $objectif->nb_femmes_beneficiaires,
                'montants_collectes' => $objectif->montants_collectes,
                'retombees_partenaires' => $objectif->retombees_partenaires,
                'nb_emplois_crees' => $objectif->nb_emplois_crees,
                'score_environnemental' => $objectif->score_environnemental,
            ])->all(),
        ];
    }

    /**
     * Formate un événement pour le formulaire d'édition.
     *
     * @return array<string, mixed>
     */
    private function mapEvenementForm(Evenement $evenement): array
    {
        $budget = $evenement->budgets->first();
        $objectif = $evenement->objectifsRse->first();

        return [
            'id' => $evenement->id,
            'titre' => $evenement->titre,
            'description' => $evenement->description,
            'type_evenement_id' => $evenement->type_evenement_id,
            'date_debut' => optional($evenement->date_debut)?->format('Y-m-d\TH:i'),
            'date_fin' => optional($evenement->date_fin)?->format('Y-m-d\TH:i'),
            'lieu_id' => $evenement->lieu_id,
            'statut' => $evenement->statut,
            'visuel_url' => $this->storageUrl($evenement->visuel),
            'montant_previsionnel' => $budget?->montant_previsionnel ?? $evenement->budget_prev ?? 0,
            'devise' => $budget?->devise ?? 'XOF',
            'type_impact' => $objectif?->type_impact,
            'nb_beneficiaires_cibles' => $objectif?->nb_beneficiaires_directs ?? 0,
            'nb_beneficiaires_indirects' => $objectif?->nb_beneficiaires_indirects ?? 0,
            'nb_associations_soutenues' => $objectif?->nb_associations_soutenues ?? 0,
            'nb_projets_accompagnes' => $objectif?->nb_projets_accompagnes ?? 0,
            'nb_femmes_beneficiaires' => $objectif?->nb_femmes_beneficiaires ?? 0,
            'montants_collectes' => $objectif?->montants_collectes ?? 0,
            'retombees_partenaires' => $objectif?->retombees_partenaires ?? 0,
            'nb_emplois_crees' => $objectif?->nb_emplois_crees ?? 0,
            'score_environnemental' => $objectif?->score_environnemental ?? 0,
        ];
    }

    /**
     * Formate le budget principal de l'événement.
     *
     * @return array<string, mixed>|null
     */
    private function mapBudget(?Budget $budget): ?array
    {
        if (! $budget) {
            return null;
        }

        return [
            'id' => $budget->id,
            'montant_previsionnel' => $budget->montant_previsionnel,
            'devise' => $budget->devise,
            'lignes_budget' => $budget->relationLoaded('lignesBudget')
                ? $budget->lignesBudget->map(fn ($ligne): array => [
                    'id' => $ligne->id,
                    'libelle' => $ligne->libelle,
                    'montant' => $ligne->montant,
                    'type' => $ligne->type,
                ])->values()->all()
                : [],
        ];
    }

    private function averageFillRate(): float
    {
        $events = Evenement::query()
            ->with(['lieu.salles'])
            ->withCount('inscriptions')
            ->get();

        if ($events->isEmpty()) {
            return 0;
        }

        $rates = $events
            ->map(function (Evenement $evenement): ?float {
                $capacity = $evenement->lieu?->salles?->sum('capacite');

                if (! $capacity || $capacity <= 0) {
                    return null;
                }

                return min(($evenement->inscriptions_count / $capacity) * 100, 100);
            })
            ->filter(fn (?float $rate): bool => $rate !== null)
            ->values();

        if ($rates->isEmpty()) {
            return 0;
        }

        return round((float) $rates->avg(), 1);
    }

    /**
     * Génère ou met à jour le fichier QR code d'un événement.
     */
    private function generateQrCode(Evenement $evenement): void
    {
        $payload = json_encode([
            'id' => $evenement->id,
            'titre' => $evenement->titre,
            'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
            'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
            'statut' => $evenement->statut,
        ], JSON_UNESCAPED_UNICODE);

        Storage::disk('public')->put(
            'qrcodes/evenement-'.$evenement->id.'.svg',
            QrCode::format('svg')->size(280)->margin(1)->generate($payload ?: (string) $evenement->id)
        );
    }

    /**
     * Crée ou met à jour l'objectif RSE principal si des données sont fournies.
     *
     * @param array<string, mixed> $data
     */
    private function upsertObjectifRse(Evenement $evenement, array $data): void
    {
        $hasPayload = collect([
            $data['type_impact'] ?? null,
            $data['nb_beneficiaires_cibles'] ?? null,
            $data['nb_beneficiaires_indirects'] ?? null,
            $data['nb_associations_soutenues'] ?? null,
            $data['nb_projets_accompagnes'] ?? null,
            $data['nb_femmes_beneficiaires'] ?? null,
            $data['montants_collectes'] ?? null,
            $data['retombees_partenaires'] ?? null,
            $data['nb_emplois_crees'] ?? null,
            $data['score_environnemental'] ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();

        if (! $hasPayload) {
            return;
        }

        $objectif = $evenement->objectifsRse()->first();

        $payload = [
            'type_impact' => $data['type_impact'] ?? 'Impact social',
            'nb_beneficiaires_directs' => (int) ($data['nb_beneficiaires_cibles'] ?? 0),
            'nb_beneficiaires_indirects' => (int) ($data['nb_beneficiaires_indirects'] ?? 0),
            'nb_associations_soutenues' => (int) ($data['nb_associations_soutenues'] ?? 0),
            'nb_projets_accompagnes' => (int) ($data['nb_projets_accompagnes'] ?? 0),
            'nb_femmes_beneficiaires' => (int) ($data['nb_femmes_beneficiaires'] ?? 0),
            'montants_collectes' => (float) ($data['montants_collectes'] ?? 0),
            'retombees_partenaires' => (float) ($data['retombees_partenaires'] ?? 0),
            'nb_emplois_crees' => (int) ($data['nb_emplois_crees'] ?? 0),
            'score_environnemental' => $data['score_environnemental'] ?? null,
        ];

        if ($objectif) {
            $objectif->update($payload);

            return;
        }

        $evenement->objectifsRse()->create($payload);
    }

    /**
     * Vérifie qu'une transition de statut est autorisée.
     */
    private function canTransitionEventStatus(string $currentStatus, string $nextStatus): bool
    {
        $allowedTransitions = [
            'brouillon' => ['publie', 'annule'],
            'publie' => ['en_cours', 'annule'],
            'en_cours' => ['termine', 'annule'],
            'termine' => [],
            'annule' => [],
        ];

        return in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true);
    }

    /**
     * Retourne l'URL publique d'un fichier stocké si disponible.
     */
    private function storageUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::url($path);
    }
}