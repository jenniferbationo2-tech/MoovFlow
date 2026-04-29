<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Services\DashboardAggregatorService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RapportController extends Controller
{
    public function __construct(
        private readonly DashboardAggregatorService $dashboardAggregatorService,
        private readonly ExportService $exportService
    ) {
    }

    /**
     * Affiche la liste des rapports disponibles.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('rapports.viewAny');

        $filters = [
            'type' => $request->string('type')->toString(),
            'periode_debut' => $request->string('periode_debut')->toString(),
            'periode_fin' => $request->string('periode_fin')->toString(),
        ];

        $query = Evenement::query()
            ->with(['typeEvenement', 'budgets'])
            ->withCount(['inscriptions', 'enquetes'])
            ->latest('date_debut');

        if ($filters['type'] !== '') {
            $query->where('type_evenement_id', $filters['type']);
        }

        if ($filters['periode_debut'] !== '') {
            $query->whereDate('date_debut', '>=', $filters['periode_debut']);
        }

        if ($filters['periode_fin'] !== '') {
            $query->whereDate('date_fin', '<=', $filters['periode_fin']);
        }

        return Inertia::render('Rapports/Index', [
            'filters' => $filters,
            'types' => TypeEvenement::query()->orderBy('nom')->get(['id', 'nom']),
            'evenements' => $query->get()->map(fn (Evenement $evenement): array => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'statut' => $evenement->statut,
                'type' => $evenement->typeEvenement?->nom ?? 'Non defini',
                'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
                'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
                'inscriptions_count' => $evenement->inscriptions_count,
                'enquetes_count' => $evenement->enquetes_count,
                'budget_previsionnel' => (float) ($evenement->budgets->first()?->montant_previsionnel ?? $evenement->budget_prev ?? 0),
            ])->values()->all(),
        ]);
    }

    /**
     * Affiche le rapport de participation d'un événement.
     */
    public function participation(Evenement $evenement): Response
    {
        Gate::authorize('rapports.viewAny');

        $stats = $this->dashboardAggregatorService->getEventStats($evenement);

        return Inertia::render('Rapports/Participation', [
            'evenement' => $this->evenementPayload($evenement),
            'rapport' => $stats['participation'],
        ]);
    }

    /**
     * Affiche le rapport financier d'un événement.
     */
    public function financier(Evenement $evenement): Response
    {
        Gate::authorize('rapports.viewAny');

        $stats = $this->dashboardAggregatorService->getEventStats($evenement);

        return Inertia::render('Rapports/Financier', [
            'evenement' => $this->evenementPayload($evenement),
            'rapport' => $stats['financier'],
        ]);
    }

    /**
     * Affiche le rapport RSE d'un événement.
     */
    public function rse(Evenement $evenement): Response
    {
        Gate::authorize('rapports.viewAny');

        $stats = $this->dashboardAggregatorService->getEventStats($evenement);

        return Inertia::render('Rapports/RSE', [
            'evenement' => $this->evenementPayload($evenement),
            'rapport' => $stats['rse'],
        ]);
    }

    /**
     * Exporte un rapport au format demandé.
     */
    public function export(Request $request, Evenement $evenement)
    {
        Gate::authorize('rapports.export');

        $validated = $request->validate([
            'type' => ['required', 'in:participation,financier,rse,presentation'],
            'format' => ['required', 'in:pdf,excel,ppt'],
        ]);

        if ($validated['format'] === 'pdf') {
            return $this->exportService->exportPDF($evenement, $validated['type']);
        }

        if ($validated['format'] === 'excel') {
            return $this->exportService->exportExcel($evenement, $validated['type']);
        }

        return $this->exportService->exportPresentation($evenement);
    }

    /**
     * @return array<string, mixed>
     */
    private function evenementPayload(Evenement $evenement): array
    {
        return [
            'id' => $evenement->id,
            'titre' => $evenement->titre,
            'statut' => $evenement->statut,
            'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
            'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
        ];
    }
}