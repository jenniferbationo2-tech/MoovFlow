<?php

namespace App\Http\Controllers;

use App\Models\Dotation;
use App\Models\Evenement;
use App\Models\User;
use App\Services\InventoryTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DotationController extends Controller
{
    public function __construct(
        private readonly InventoryTrackingService $inventoryTrackingService
    ) {
    }

    /**
     * Affiche les équipements distribués.
     */
    public function index(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        $dotations = Dotation::query()
            ->with('user')
            ->where('evenement_id', $evenement->id)
            ->latest('date_remise')
            ->get();

        return Inertia::render('Logistique/Dotations/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'dotations' => $dotations->map(fn (Dotation $dotation): array => $this->mapDotation($dotation))->all(),
            'participants' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->toArray(),
            'stats' => $this->inventoryTrackingService->getStats($evenement->id),
            'alerts' => $this->inventoryTrackingService->alertsLowStock($evenement->id),
        ]);
    }

    /**
     * Distribue un équipement à un participant.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'equipement' => ['required', 'string', 'max:255'],
            'date_remise' => ['required', 'date'],
            'date_retour_prevue' => ['nullable', 'date', 'after_or_equal:date_remise'],
            'etat_depart' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['evenement_id'] = $evenement->id;

        Dotation::query()->create($validated);

        return back()->with('success', 'Équipement distribué avec succès.');
    }

    /**
     * Enregistre le retour d'un équipement.
     */
    public function returnItem(Request $request, Dotation $dotation): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $request->validate([
            'date_retour' => ['required', 'date', 'after_or_equal:date_remise'],
            'etat_retour' => ['required', 'string', 'max:100'],
        ]);

        $dotation->update($validated);

        return back()->with('success', 'Retour d’équipement enregistré.');
    }

    /**
     * Affiche un suivi global des équipements.
     */
    public function tracking(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        $dotations = Dotation::query()
            ->with('user')
            ->where('evenement_id', $evenement->id)
            ->latest('date_remise')
            ->get();

        return Inertia::render('Logistique/Dotations/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'dotations' => $dotations->map(fn (Dotation $dotation): array => $this->mapDotation($dotation))->all(),
            'participants' => User::query()->orderBy('name')->get(['id', 'name', 'email'])->toArray(),
            'stats' => $this->inventoryTrackingService->getStats($evenement->id),
            'alerts' => $this->inventoryTrackingService->alertsLowStock($evenement->id),
            'focusSection' => 'tracking',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDotation(Dotation $dotation): array
    {
        $status = 'en_cours';

        if ($dotation->date_retour) {
            $status = 'retourne';
        } elseif ($dotation->date_retour_prevue && $dotation->date_retour_prevue->isPast()) {
            $status = 'manquant';
        }

        return [
            'id' => $dotation->id,
            'equipement' => $dotation->equipement,
            'participant' => [
                'id' => $dotation->user?->id,
                'name' => $dotation->user?->name,
                'email' => $dotation->user?->email,
            ],
            'date_remise' => optional($dotation->date_remise)?->toDateString(),
            'date_retour_prevue' => optional($dotation->date_retour_prevue)?->toDateString(),
            'date_retour' => optional($dotation->date_retour)?->toDateString(),
            'etat_depart' => $dotation->etat_depart,
            'etat_retour' => $dotation->etat_retour,
            'statut' => $status,
        ];
    }
}