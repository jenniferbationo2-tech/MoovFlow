<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Ressource;
use App\Services\ResourcePlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RessourceController extends Controller
{
    public function __construct(
        private readonly ResourcePlanningService $resourcePlanningService
    ) {
    }

    /**
     * Affiche les ressources d'un événement par type.
     */
    public function index(Request $request, Evenement $evenement): Response
    {
        abort_unless($request->user()?->can('logistique.view'), 403);

        $filters = [
            'type' => $request->string('type')->toString(),
            'statut' => $request->string('statut')->toString(),
        ];

        $query = $evenement->ressources()
            ->when($filters['type'] !== '', fn ($builder) => $builder->where('type', $filters['type']))
            ->when($filters['statut'] !== '', fn ($builder) => $builder->where('statut', $filters['statut']));

        $ressources = $query
            ->orderBy('type')
            ->orderBy('nom')
            ->get()
            ->map(fn (Ressource $ressource): array => $this->mapRessource($ressource))
            ->all();

        $grouped = collect($ressources)
            ->groupBy('type')
            ->map(fn ($items) => array_values($items->all()))
            ->all();

        return Inertia::render('Logistique/Ressources/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
                'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
            ],
            'filters' => $filters,
            'types' => self::types(),
            'statuts' => self::statuts(),
            'ressources' => $grouped,
            'stats' => [
                'total' => $evenement->ressources()->count(),
                'disponibles' => $evenement->ressources()->where('statut', 'disponible')->count(),
                'reservees' => $evenement->ressources()->where('statut', 'reserve')->count(),
                'utilisees' => $evenement->ressources()->where('statut', 'utilise')->count(),
            ],
        ]);
    }

    /**
     * Crée une ressource logistique.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $this->validateRessource($request);

        $evenement->ressources()->create($validated);

        return back()->with('success', 'Ressource ajoutée avec succès.');
    }

    /**
     * Met à jour une ressource existante.
     */
    public function update(Request $request, Ressource $ressource): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $this->validateRessource($request);

        $ressource->update($validated);

        return back()->with('success', 'Ressource mise à jour avec succès.');
    }

    /**
     * Supprime une ressource.
     */
    public function destroy(Ressource $ressource): RedirectResponse
    {
        abort_unless(request()->user()?->can('logistique.manage'), 403);

        $ressource->delete();

        return back()->with('success', 'Ressource supprimée avec succès.');
    }

    /**
     * Vérifie la disponibilité d'une salle sur une période.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('logistique.view'), 403);

        $validated = $request->validate([
            'salle_id' => ['nullable', 'exists:salles,id'],
            'debut' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:debut'],
            'capacite_min' => ['nullable', 'integer', 'min:0'],
        ]);

        $isAvailable = $validated['salle_id'] ?? null
            ? $this->resourcePlanningService->checkConflicts(
                (int) $validated['salle_id'],
                $validated['debut'],
                $validated['fin']
            )
            : null;

        $suggestions = $this->resourcePlanningService
            ->suggestAvailableRooms(
                $validated['debut'],
                $validated['fin'],
                (int) ($validated['capacite_min'] ?? 0)
            )
            ->values()
            ->all();

        return response()->json([
            'available' => $isAvailable,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Retourne les types de ressources pris en charge.
     *
     * @return array<int, array<string, string>>
     */
    public static function types(): array
    {
        return [
            ['value' => 'transport', 'label' => 'Transport'],
            ['value' => 'materiel', 'label' => 'Matériel'],
            ['value' => 'restauration', 'label' => 'Restauration'],
        ];
    }

    /**
     * Retourne les statuts de ressources.
     *
     * @return array<int, array<string, string>>
     */
    public static function statuts(): array
    {
        return [
            ['value' => 'disponible', 'label' => 'Disponible'],
            ['value' => 'reserve', 'label' => 'Réservée'],
            ['value' => 'utilise', 'label' => 'Utilisée'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRessource(Ressource $ressource): array
    {
        return [
            'id' => $ressource->id,
            'type' => $ressource->type,
            'nom' => $ressource->nom,
            'description' => $ressource->description,
            'quantite' => $ressource->quantite,
            'statut' => $ressource->statut,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRessource(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_column(self::types(), 'value'))],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantite' => ['required', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(array_column(self::statuts(), 'value'))],
        ]);
    }
}